<?php

namespace App\Filament\Resources\OwnerProductRequestResource\Pages;

use App\Filament\Resources\OwnerProductRequestResource;
use App\Models\Product;
use App\Models\ProductRequest;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class CreateOwnerProductRequest extends Page
{
    protected static string $resource = OwnerProductRequestResource::class;

    protected static string $view = 'filament.resources.owner-product-request-resource.pages.create-owner-product-request';

    public string $search = '';

    public ?int $selectedProductId = null;

    public int $jumlah = 1;

    public function getTitle(): string|Htmlable
    {
        return 'Buat Usulan Produksi';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return static::getResource()::canCreate();
    }

    public function getMaxWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->select([
                'products.id',
                'products.nama',
                'products.foto',
                'products.is_discontinued',
            ])
            ->when($this->search, function ($query) {
                $keyword = '%'.strtolower(trim($this->search)).'%';
                $query->whereRaw('LOWER(nama) LIKE ?', [$keyword]);
            })
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function selectedProduct()
    {
        if (! $this->selectedProductId) {
            return null;
        }

        $product = $this->productQueryWithProductionInsights()
            ->whereKey($this->selectedProductId)
            ->first();

        if ($product) {
            $product->setAttribute(
                'terjual_bulan_ini',
                (int) $product->terjual_attiin_bulan_ini + (int) $product->terjual_mitra_bulan_ini,
            );
        }

        return $product;
    }

    public function selectProduct(int $productId): void
    {
        abort_unless(Product::query()->whereKey($productId)->exists(), 404);

        $this->selectedProductId = $productId;
        $this->jumlah = 1;
        $this->resetValidation();
    }

    public function updateJumlah(mixed $newJumlah): void
    {
        if (! $this->selectedProductId) {
            $this->jumlah = 1;

            return;
        }

        $this->jumlah = max(1, (int) $newJumlah);
        $this->resetValidation('jumlah');
    }

    public function submit(): void
    {
        $user = Auth::user();

        $this->validate([
            'selectedProductId' => ['required', 'exists:products,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ], [
            'selectedProductId.required' => 'Pilih produk terlebih dahulu.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1 pcs.',
        ]);

        ProductRequest::create([
            'user_id' => $user->id,
            'partner_id' => null,
            'product_id' => $this->selectedProductId,
            'tipe_request' => 'produksi_owner',
            'jumlah' => $this->jumlah,
            'diskon_persen' => 0,
            'status' => ProductRequest::STATUS_PENDING,
        ]);

        Notification::make()
            ->success()
            ->title('Usulan Berhasil Dibuat!')
            ->body('Usulan produksi Anda telah masuk ke sistem dan menunggu persetujuan Admin.')
            ->send();

        $this->redirect(static::getResource()::getUrl('index'));
    }

    private function productQueryWithProductionInsights(): Builder
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $today = now()->startOfDay();
        $expiryWarningDate = now()->addDays(30)->endOfDay();

        return Product::query()
            ->select([
                'products.id',
                'products.nama',
                'products.foto',
                'products.harga_jual',
                'products.estimasi_masak',
                'products.is_discontinued',
            ])
            ->selectSub(function ($query) {
                $query->from('product_batches as stock_batches')
                    ->selectRaw('COALESCE(SUM(stock_batches.stok_toko), 0)')
                    ->whereColumn('stock_batches.product_id', 'products.id')
                    ->whereDate('stock_batches.tanggal_kedaluwarsa', '>=', now());
            }, 'stok_attiin')
            ->selectSub(function ($query) use ($monthStart, $monthEnd) {
                $query->from('transaction_details as details')
                    ->join('transactions as monthly_transactions', 'monthly_transactions.id', '=', 'details.transaction_id')
                    ->join('product_batches as transaction_batches', 'transaction_batches.id', '=', 'details.product_batch_id')
                    ->selectRaw('COALESCE(SUM(details.qty), 0)')
                    ->whereColumn('transaction_batches.product_id', 'products.id')
                    ->where('monthly_transactions.status', 'Selesai')
                    ->whereBetween('monthly_transactions.created_at', [$monthStart, $monthEnd]);
            }, 'terjual_attiin_bulan_ini')
            ->selectSub(function ($query) use ($monthStart, $monthEnd) {
                $query->from('consignment_returns as monthly_returns')
                    ->join('product_batches as consignment_batches', 'consignment_batches.id', '=', 'monthly_returns.product_batch_id')
                    ->selectRaw('COALESCE(SUM(monthly_returns.terjual), 0)')
                    ->whereColumn('consignment_batches.product_id', 'products.id')
                    ->where('monthly_returns.status', 'selesai')
                    ->whereBetween('monthly_returns.created_at', [$monthStart, $monthEnd]);
            }, 'terjual_mitra_bulan_ini')
            ->selectSub(function ($query) {
                $query->from('product_requests as active_partner_requests')
                    ->selectRaw('COALESCE(SUM(active_partner_requests.jumlah), 0)')
                    ->whereColumn('active_partner_requests.product_id', 'products.id')
                    ->where('active_partner_requests.tipe_request', 'restok_apotek')
                    ->whereIn('active_partner_requests.status', [
                        ProductRequest::STATUS_PENDING,
                        ProductRequest::STATUS_DIPROSES,
                    ]);
            }, 'kebutuhan_mitra_aktif')
            ->selectSub(function ($query) use ($today, $expiryWarningDate) {
                $query->from('product_batches as expiring_batches')
                    ->selectRaw('COALESCE(SUM(expiring_batches.stok_toko), 0)')
                    ->whereColumn('expiring_batches.product_id', 'products.id')
                    ->where('expiring_batches.stok_toko', '>', 0)
                    ->whereBetween('expiring_batches.tanggal_kedaluwarsa', [$today, $expiryWarningDate]);
            }, 'stok_mendekati_kedaluwarsa');
    }
}
