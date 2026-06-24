<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Sales;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On; // TAMBAHAN: Wajib diimpor agar bisa mendengar sinyal JS

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
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
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

    // TAMBAHAN: Telinga untuk menangkap hasil scan dari kamera QR Code
    #[On('process-barcode')]
    public function scanBarcode($sku = null)
    {
        $skuTarget = trim($sku ?? $this->search);
        if (empty($skuTarget)) return;

        // Cari produk berdasarkan SKU (exact match)
        $product = Product::where('sku', $skuTarget)->first();

        // Fallback: cari dengan LIKE jika exact match gagal (misal ada spasi/newline dari scanner)
        if (!$product) {
            $product = Product::where('sku', 'like', '%' . $skuTarget . '%')->first();
        }

        if (!$product) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => 'SKU ' . $skuTarget . ' tidak ditemukan']]);
            $this->search = '';
            return;
        }

        // addToCart sekarang return boolean agar tahu berhasil atau tidak
        $added = $this->addToCart($product->id);

        if ($added) {
            $this->dispatch('product-added', [['name' => $product->nama]]);
        }

        $this->search = '';
    }

    public function addToCart($productId): bool
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => 'Produk tidak ditemukan']]);
            return false;
        }

        if ($product->stok_toko <= 0) {
            $this->dispatch('play-error-beep');
            $this->dispatch('stock-warning', [['name' => $product->nama . ' (STOK HABIS)']]);
            return false;
        }

        if (isset($this->cart[$product->id])) {
            if ($this->cart[$product->id]['qty'] >= $product->stok_toko) {
                $this->dispatch('play-error-beep');
                $this->dispatch('stock-warning', [['name' => $product->nama . ' (melebihi stok)']]);
                return false;
            }
            $this->cart[$product->id]['qty']++;
            $this->cart[$product->id]['subtotal'] = $this->cart[$product->id]['qty'] * $this->cart[$product->id]['harga'];
        } else {
            $this->cart[$product->id] = [
                'id' => $product->id,
                'sku' => $product->sku,
                'nama' => $product->nama,
                'harga' => $product->harga_jual,
                'qty' => 1,
                'subtotal' => $product->harga_jual,
            ];
        }
        $this->dispatch('play-beep');
        return true;
    }

    public function updateQty($productId, $newQty)
    {
        $newQty = (int) $newQty;
        if ($newQty <= 0) {
            $this->removeItem($productId);
            return;
        }

        $product = Product::find($productId);
        if ($newQty > $product->stok_toko) {
            $this->dispatch('stock-warning', [['name' => $product->nama]]);
            return;
        }

        $this->cart[$productId]['qty'] = $newQty;
        $this->cart[$productId]['subtotal'] = $newQty * $this->cart[$productId]['harga'];
    }

    public function removeItem($productId)
    {
        unset($this->cart[$productId]);
        if (empty($this->cart)) {
            $this->resetCart();
        }
    }

    public function resetCart()
    {
        $this->cart = [];
        $this->diskon = 0;
        $this->bayar = null;
        $this->dispatch('cart-cleared');
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
                    'product_id' => $item['id'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                ]);

                Product::find($item['id'])->decrement('stok_toko', $item['qty']);
            }

            $this->lastTransactionId = $transaction->id;
        });

        $this->resetCart();
        $this->dispatch('transaction-success');
        $this->showReceiptModal = true;
    }

    // TAMBAHAN: Telinga untuk menangkap sinyal tombol "Esc" atau cetak nota dari Javascript
    #[On('close-receipt')]
    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->resetCart();
    }
}
