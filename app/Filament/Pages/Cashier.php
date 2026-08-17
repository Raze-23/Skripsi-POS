<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Sales;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class Cashier extends Page
{
    protected static ?string $navigationIcon = 'heroicon-s-tv';

    protected static ?string $cluster = Sales::class;

    protected static ?string $navigationLabel = 'Mesin Kasir';

    protected static ?string $title = 'Mesin Kasir';

    protected static string $view = 'filament.pages.cashier';

    public string $search = '';

    public array $cart = [];

    public $diskon = 0;

    public $bayar = null;

    public bool $showReceiptModal = false;

    public ?int $lastTransactionId = null;

    public int $kembalianAkhir = 0;

    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->search, function ($query) {
                $keyword = '%' . strtolower(trim($this->search)) . '%';
                $query->where(function ($q) use ($keyword) {
                    $q->whereRaw('LOWER(nama) LIKE ?', [$keyword])
                        ->orWhereHas(
                            'productBatches',
                            fn ($qb) => $qb->whereRaw('LOWER(batch_code) LIKE ?', [$keyword])
                        );
                });
            })
            ->whereHas('productBatches', fn($q) => $q->where('stok_toko', '>', 0))
            ->with(['productBatches' => fn($q) => $q->where('stok_toko', '>', 0)->orderBy('tanggal_kedaluwarsa')])
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function subtotal_cart()
    {
        return collect($this->cart)->sum('subtotal');
    }

    #[Computed]
    public function total_harga()
    {
        $subtotal = $this->subtotal_cart;
        $diskonNominal = ($subtotal * ((int) $this->diskon ?: 0)) / 100;
        return max(0, $subtotal - $diskonNominal);
    }

    #[Computed]
    public function kembalian()
    {
        $uangBayar = (int) $this->bayar ?: 0;
        return $uangBayar - $this->total_harga;
    }


    #[On('process-qrcode')]
    public function scanQRCode($sku = null)
    {
        $skuTarget = trim($sku);
        if (empty($skuTarget)) return;

        $batch = ProductBatch::with('product')
            ->where('batch_code', $skuTarget)
            ->first();

        if (!$batch) {
            $batch = ProductBatch::with('product')
                ->where('batch_code', 'like', '%' . $skuTarget . '%')
                ->first();
        }

        if ($batch) {
            $added = $this->addToCartByBatch($batch);

            if ($added && $sku !== null) {
                Notification::make()
                    ->title('Berhasil masuk ke keranjang')
                    ->body($batch->product->nama . ' · Batch ' . $batch->batch_code)
                    ->success()
                    ->icon('heroicon-o-qr-code')
                    ->iconColor('success')
                    ->duration(2500)
                    ->send();
            }
            return;
        }

        $this->dispatch('play-error-beep');
        $this->dispatch('stock-warning', [['name' => 'Barcode "' . $skuTarget . '" tidak dikenali']]);
    }

    public function addToCart($productId): bool
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => 'Produk tidak ditemukan']]);
            return false;
        }

        $currentQty = 0;
        foreach ($this->cart as $item) {
            if (isset($item['product_id']) && $item['product_id'] == $productId) {
                $currentQty += $item['qty'];
            }
        }

        $result = $this->recalculateProductFifo($productId, $currentQty + 1);

        if ($result) {
            $this->dispatch('play-beep');
        }

        return $result;
    }

    public function addToCartByBatch(ProductBatch $batch): bool
    {
        if ($batch->stok_toko <= 0) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => $batch->product->nama . ' (STOK HABIS)']]);
            return false;
        }

        if ($batch->tanggal_kedaluwarsa && Carbon::parse($batch->tanggal_kedaluwarsa)->lt(now()->startOfDay())) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => $batch->product->nama . ' (KEDALUWARSA)']]);
            return false;
        }

        $batch->loadMissing('product');
        $productId = $batch->product_id;

        $currentQty = 0;
        foreach ($this->cart as $item) {
            if (isset($item['product_id']) && $item['product_id'] == $productId) {
                $currentQty += $item['qty'];
            }
        }

        $result = $this->recalculateProductFifo($productId, $currentQty + 1);

        if ($result) {
            $this->dispatch('play-beep');
        }

        return $result;
    }

    public function updateQty($batchId, $newQty)
    {
        $newQty = (int) $newQty;

        if ($newQty < 1) {
            unset($this->cart[$batchId]);
            if (empty($this->cart)) {
                $this->resetCart();
            }
            return;
        }

        if (!isset($this->cart[$batchId])) {
            return;
        }

        $productId = $this->cart[$batchId]['product_id'];

        $totalCurrentQty = 0;
        foreach ($this->cart as $id => $item) {
            if (isset($item['product_id']) && $item['product_id'] == $productId && $id != $batchId) {
                $totalCurrentQty += $item['qty'];
            }
        }

        $totalDesiredQty = $totalCurrentQty + $newQty;

        $this->recalculateProductFifo($productId, $totalDesiredQty);
    }

    public function recalculateProductFifo($productId, $totalRequestedQty): bool
    {
        $batches = ProductBatch::with('product')
            ->where('product_id', $productId)
            ->where('stok_toko', '>', 0)
            ->where(function ($q) {
                $q->whereNull('tanggal_kedaluwarsa')
                  ->orWhere('tanggal_kedaluwarsa', '>=', now()->startOfDay());
            })
            ->orderBy('tanggal_kedaluwarsa')
            ->get();

        $totalStokToko = $batches->sum('stok_toko');

        if ($totalStokToko <= 0) {
            $productName = Product::find($productId)?->nama ?? 'Produk';
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => $productName . ' (STOK HABIS)']]);
            return false;
        }

        if ($totalRequestedQty > $totalStokToko) {
            $productName = $batches->first()?->product?->nama ?? 'Produk';
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => $productName . ' (Maks: ' . $totalStokToko . ')']]);
            $totalRequestedQty = $totalStokToko;
        }

        foreach ($this->cart as $key => $item) {
            if (isset($item['product_id']) && $item['product_id'] == $productId) {
                unset($this->cart[$key]);
            }
        }

        $remainingQty = $totalRequestedQty;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) {
                break;
            }

            $takeQty = min($remainingQty, $batch->stok_toko);

            $this->cart[$batch->id] = [
                'id' => $batch->id,
                'product_id' => $productId,
                'batch_code' => $batch->batch_code,
                'nama' => $batch->product->nama ?? 'Produk',
                'harga' => $batch->product->harga_jual,
                'qty' => $takeQty,
                'subtotal' => $takeQty * $batch->product->harga_jual,
            ];

            $remainingQty -= $takeQty;
        }

        return true;
    }

    public function removeItem($batchId)
    {
        unset($this->cart[$batchId]);
        if (empty($this->cart)) {
            $this->resetCart();
        }
    }

    public function resetCart()
    {
        $this->cart = [];
        $this->diskon = 0;
        $this->bayar = null;
    }

    public function clearCart()
    {
        if (empty($this->cart)) {
            return;
        }

        $itemCount = (int) collect($this->cart)->sum('qty');

        $this->resetCart();

        Notification::make()
            ->title('Keranjang dikosongkan')
            ->body($itemCount . ' item telah dihapus dari keranjang')
            ->icon('heroicon-o-trash')
            ->iconColor('gray')
            ->color('gray')
            ->duration(2500)
            ->send();
    }

    public function submitTransaction()
    {
        if (empty($this->cart)) {
            return;
        }

        $this->diskon = max(0, min(100, (int) $this->diskon));

        $uangBayar = (int) $this->bayar ?: 0;
        if ($uangBayar < $this->total_harga) {
            return;
        }

        $this->kembalianAkhir = max(0, $uangBayar - $this->total_harga);

        try {
            DB::transaction(function () use ($uangBayar) {
                foreach ($this->cart as $item) {
                    $batch = ProductBatch::lockForUpdate()->find($item['id']);
                    if (!$batch || $batch->stok_toko < $item['qty']) {
                        $nama = $batch?->product?->nama ?? 'Produk';
                        throw new \RuntimeException("Stok {$nama} (Batch: {$item['batch_code']}) tidak mencukupi.");
                    }
                }

                $transaction = Transaction::create([
                    'kasir_id' => Auth::id(),
                    'total_harga' => $this->total_harga,
                    'diskon_persen' => (int) $this->diskon ?: 0,
                    'nominal_bayar' => $uangBayar,
                    'nominal_kembalian' => $this->kembalianAkhir,
                    'status' => 'Selesai',
                ]);

                foreach ($this->cart as $item) {
                    $transaction->details()->create([
                        'product_batch_id' => $item['id'],
                        'qty' => $item['qty'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    ProductBatch::where('id', $item['id'])
                        ->where('stok_toko', '>=', $item['qty'])
                        ->decrement('stok_toko', $item['qty']);
                }

                $this->lastTransactionId = $transaction->id;
            });
        } catch (\RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Transaksi Gagal!')
                ->body($e->getMessage() . ' Silakan cek ulang keranjang.')
                ->icon('heroicon-o-exclamation-triangle')
                ->send();
            return;
        }

        $this->resetCart();
        $this->dispatch('transaction-success');
        $this->showReceiptModal = true;
    }


    #[On('close-receipt')]
    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->resetCart();
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'kasir';
    }
}