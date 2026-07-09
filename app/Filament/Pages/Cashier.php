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
                $query->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhereHas('productBatches', fn($q) => $q->where('batch_code', 'like', '%' . $this->search . '%'));
            })
            ->with(['productBatches' => fn($q) => $q->where('stok_toko', '>', 0)->orderBy('tanggal_kedaluwarsa')])
            ->limit(12)
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


    #[On('process-barcode')]
    public function scanBarcode($sku = null)
    {
        $skuTarget = trim($sku ?? $this->search);
        if (empty($skuTarget)) return;

        $batch = ProductBatch::with('product')
            ->where('batch_code', $skuTarget)
            ->first();

        if (!$batch) {
            $batch = ProductBatch::with('product')
                ->where('batch_code', 'like', '%' . $skuTarget . '%')
                ->first();
        }

        if (!$batch) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => 'Kode batch ' . $skuTarget . ' tidak ditemukan']]);
            $this->search = '';
            return;
        }

        $added = $this->addToCartByBatch($batch);

        if ($added) {
            $this->dispatch('product-added', [['name' => $batch->product->nama]]);
        }

        $this->search = '';
    }

    /**
     * Add to cart by clicking a product tile (FIFO: auto-selects closest-expiry batch).
     */
    public function addToCart($productId): bool
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => 'Produk tidak ditemukan']]);
            return false;
        }

        // Find FIFO batch: closest expiry, not expired, has available stock (accounting for cart)
        $batches = $product->productBatches()
            ->where('stok_toko', '>', 0)
            ->where(function ($q) {
                $q->whereNull('tanggal_kedaluwarsa')
                  ->orWhere('tanggal_kedaluwarsa', '>=', now()->startOfDay());
            })
            ->orderBy('tanggal_kedaluwarsa')
            ->get();

        $batch = null;
        foreach ($batches as $b) {
            $inCart = $this->cart[$b->id]['qty'] ?? 0;
            if ($inCart < $b->stok_toko) {
                $batch = $b;
                break;
            }
        }

        if (!$batch) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => $product->nama . ' (STOK HABIS)']]);
            return false;
        }

        $batch->loadMissing('product');
        return $this->addToCartByBatch($batch);
    }

    /**
     * Add a specific batch to cart (used by both scan and tile click).
     */
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

        if (isset($this->cart[$batch->id])) {
            if ($this->cart[$batch->id]['qty'] >= $batch->stok_toko) {
                $this->dispatch('play-error-beep');
                $this->dispatch('stock-warning', [['name' => $batch->product->nama . ' (melebihi stok yang tersedia)']]);
                return false;
            }
            $this->cart[$batch->id]['qty']++;
            $this->cart[$batch->id]['subtotal'] = $this->cart[$batch->id]['qty'] * $this->cart[$batch->id]['harga'];
        } else {
            $this->cart[$batch->id] = [
                'id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'nama' => $batch->product->nama,
                'harga' => $batch->product->harga_jual,
                'qty' => 1,
                'subtotal' => $batch->product->harga_jual,
            ];
        }
        $this->dispatch('play-beep');
        return true;
    }

    public function updateQty($batchId, $newQty)
    {
        $newQty = (int) $newQty;
        if ($newQty <= 0) {
            $this->removeItem($batchId);
            return;
        }

        $batch = ProductBatch::with('product')->find($batchId);
        if ($newQty > $batch->stok_toko) {
            $this->dispatch('stock-warning', [['name' => $batch->product->nama]]);
            return;
        }

        $this->cart[$batchId]['qty'] = $newQty;
        $this->cart[$batchId]['subtotal'] = $newQty * $this->cart[$batchId]['harga'];
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

    public function submitTransaction()
    {
        if (empty($this->cart)) {
            return;
        }

        $uangBayar = (int) $this->bayar ?: 0;
        if ($uangBayar < $this->total_harga) {
            Notification::make()->danger()->title('Uang Pembayaran Kurang!')->send();
            return;
        }

        $this->kembalianAkhir = max(0, $uangBayar - $this->total_harga);

        DB::transaction(function () use ($uangBayar) {
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

                ProductBatch::find($item['id'])->decrement('stok_toko', $item['qty']);
            }

            $this->lastTransactionId = $transaction->id;
        });

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
