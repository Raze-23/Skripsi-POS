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

    public $bayar = null; // Pakai null agar placeholder '0' di form bisa muncul

    public bool $showReceiptModal = false;

    public ?int $lastTransactionId = null;

    public int $kembalianAkhir = 0;

    // =======================================================
    // COMPUTED PROPERTIES (Kalkulasi Real-Time Otomatis Livewire)
    // =======================================================

    // 1. Menampilkan daftar produk di grid kiri (Bisa dicari via SKU atau Nama)
    #[Computed]
    public function products()
    {
            return Product::query() // HAPUS where('stok_toko', '>', 0)
                ->when($this->search, function ($query) {
                    $query->where('nama', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                })
                ->limit(12)
                ->get();
    }

    // 2. Menghitung subtotal keranjang murni
    #[Computed]
    public function subtotal_cart()
    {
        return collect($this->cart)->sum('subtotal');
    }

    // 3. Menghitung tagihan akhir setelah dipotong diskon persen
    #[Computed]
    public function total_harga()
    {
        $subtotal = $this->subtotal_cart;
        $diskonNominal = ($subtotal * ((int) $this->diskon ?: 0)) / 100;
        return max(0, $subtotal - $diskonNominal);
    }

    // 4. Menghitung kembalian atau status minus (kekurangan uang)
    #[Computed]
    public function kembalian()
    {
        $uangBayar = (int) $this->bayar ?: 0;
        return $uangBayar - $this->total_harga;
    }

    // =======================================================
    // FUNGSI AKSI KASIR
    // =======================================================

    // Menangkap input dari Scanner Barcode (dieksekusi saat tombol Enter otomatis tertekan)
    public function scanBarcode()
    {
        $sku = trim($this->search);
        if (empty($sku)) return;

        $product = Product::where('sku', $sku)->first();

        if (!$product) {
            $this->dispatch('stock-warning', [['name' => 'SKU tidak ditemukan di database']]);
            $this->search = '';
            return;
        }

        $this->addToCart($product->id);
        $this->search = ''; // Kosongkan lagi form scanner agar siap memindai barang berikutnya
    }

    // Memasukkan barang ke keranjang
    public function addToCart($productId)
    {
        $product = Product::find($productId);

        // Cek ketersediaan stok
        if (!$product || $product->stok_toko <= 0) {
            $this->dispatch('stock-warning', [['name' => $product->nama ?? 'Produk']]);
            return;
        }

        // Jika sudah ada di keranjang, tambah Qty-nya
        if (isset($this->cart[$product->id])) {
            if ($this->cart[$product->id]['qty'] >= $product->stok_toko) {
                $this->dispatch('stock-warning', [['name' => $product->nama]]);
                return;
            }
            $this->cart[$product->id]['qty']++;
            $this->cart[$product->id]['subtotal'] = $this->cart[$product->id]['qty'] * $this->cart[$product->id]['harga'];
        } else {
            // Jika produk baru
            $this->cart[$product->id] = [
                'id' => $product->id,
                'sku' => $product->sku,
                'nama' => $product->nama,
                'harga' => $product->harga_jual,
                'qty' => 1,
                'subtotal' => $product->harga_jual,
            ];
        }

        // Panggil efek suara "Tiiit" dan Notifikasi Toast
        $this->dispatch('play-beep');
        $this->dispatch('product-added', [['name' => $product->nama]]);
    }

    // Tambah/Kurang Qty Manual via tombol (+) dan (-) di keranjang
    public function updateQty($productId, $newQty)
    {
        $newQty = (int) $newQty;
        if ($newQty <= 0) {
            $this->removeItem($productId); // <-- PERBAIKAN DI SINI
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
    }

    public function resetCart()
    {
        $this->cart = [];
        $this->diskon = 0;
        $this->bayar = null;
        $this->dispatch('cart-cleared');
    }

    // =======================================================
    // EKSEKUSI PEMBAYARAN KE DATABASE
    // =======================================================
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
        // 1. BEKUKAN ANGKA KEMBALIAN SEBELUM KERANJANG DIHANCURKAN
        $this->kembalianAkhir = max(0, $uangBayar - $this->total_harga);
        // 2. SIMPAN KE DATABASE
        DB::transaction(function () use ($uangBayar) {
            $transaction = Transaction::create([
                'kasir_id' => Auth::id(),
                'total_harga' => $this->total_harga,
                'diskon_persen' => (int) $this->diskon ?: 0,
                'nominal_bayar' => $uangBayar,
                'nominal_kembalian' => $this->kembalianAkhir, // Gunakan angka yang dibekukan
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
    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->resetCart();
    }
}
