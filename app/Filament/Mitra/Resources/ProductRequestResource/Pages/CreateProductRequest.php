<?php

namespace App\Filament\Mitra\Resources\ProductRequestResource\Pages;

use App\Filament\Mitra\Resources\ProductRequestResource;
use App\Models\Product;
use App\Models\ProductRequest;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class CreateProductRequest extends Page
{
    protected static string $resource = ProductRequestResource::class;

    protected static string $view = 'filament.mitra.resources.product-request-resource.pages.create-product-request';

    public string $search = '';

    public ?int $selectedProductId = null;

    public int $jumlah = 1;

    public ?string $diskonPersen = null;

    public ?int $detailProductId = null;

    public function getTitle(): string|Htmlable
    {
        return 'Buat Request Produk';
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
        $partnerId = Auth::user()?->partner_id;

        $products = Product::query()
            ->select(['id', 'nama', 'foto', 'harga_jual', 'estimasi_masak', 'deskripsi', 'is_discontinued'])
            ->when($this->search, function ($query) {
                $keyword = '%'.strtolower(trim($this->search)).'%';
                $query->whereRaw('LOWER(nama) LIKE ?', [$keyword]);
            })
            ->orderBy('nama')
            ->get();

        $ranks = $this->bestSellerRanks($partnerId);

        return $products->each(fn (Product $product) => $this->attachBestSellerRanks($product, $ranks));
    }

    #[Computed]
    public function selectedProduct()
    {
        if (! $this->selectedProductId) {
            return null;
        }

        $product = Product::query()
            ->select(['id', 'nama', 'foto', 'harga_jual', 'estimasi_masak', 'deskripsi', 'is_discontinued'])
            ->find($this->selectedProductId);

        if (! $product) {
            return null;
        }

        return $this->attachBestSellerRanks(
            $product,
            $this->bestSellerRanks(Auth::user()?->partner_id),
        );
    }

    #[Computed]
    public function detailProduct()
    {
        if (! $this->detailProductId) {
            return null;
        }

        $product = Product::query()
            ->withSum('productBatches as stok_attiin', 'stok_toko')
            ->find($this->detailProductId);

        if (! $product) {
            return null;
        }

        return $this->attachBestSellerRanks(
            $product,
            $this->bestSellerRanks(Auth::user()?->partner_id),
        );
    }

    public function selectProduct(int $productId): void
    {
        abort_unless(Product::query()->whereKey($productId)->exists(), 404);

        $this->selectedProductId = $productId;
        $this->jumlah = 1;
        $this->resetValidation();
    }

    public function openDetailModal(int $productId): void
    {
        abort_unless(Product::query()->whereKey($productId)->exists(), 404);

        $this->detailProductId = $productId;
        $this->dispatch('open-modal', id: 'product-detail-modal');
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
            'diskonPersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'selectedProductId.required' => 'Pilih produk terlebih dahulu.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1 pcs.',
        ]);

        ProductRequest::create([
            'user_id' => $user->id,
            'partner_id' => $user->partner_id,
            'product_id' => $this->selectedProductId,
            'tipe_request' => 'restok_apotek',
            'jumlah' => $this->jumlah,
            'diskon_persen' => round((float) ($this->diskonPersen ?? 0), 2),
            'status' => ProductRequest::STATUS_PENDING,
        ]);

        Notification::make()
            ->success()
            ->title('Request Berhasil Dibuat!')
            ->body('Permintaan produk Anda telah masuk dan menunggu keputusan Admin.')
            ->send();

        $this->redirect(static::getResource()::getUrl('index'));
    }

    /**
     * Ranking Attiin hanya memakai transaksi kasir Attiin, sedangkan ranking
     * mitra hanya memakai retur penjualan dari apotek yang sedang login.
     */
    private function bestSellerRanks(?int $partnerId): array
    {
        $periodStart = now()->startOfYear();
        $periodEnd = now()->endOfYear();

        $attiinProductIds = DB::table('transaction_details as td')
            ->join('transactions as t', 't.id', '=', 'td.transaction_id')
            ->join('product_batches as pb', 'pb.id', '=', 'td.product_batch_id')
            ->where('t.status', 'Selesai')
            ->whereBetween('t.created_at', [$periodStart, $periodEnd])
            ->selectRaw('pb.product_id, SUM(td.qty) as total_terjual')
            ->groupBy('pb.product_id')
            ->havingRaw('SUM(td.qty) > 0')
            ->orderByDesc('total_terjual')
            ->orderBy('pb.product_id')
            ->limit(3)
            ->pluck('pb.product_id');

        $mitraProductIds = collect();

        if ($partnerId) {
            $mitraProductIds = DB::table('consignment_returns as cr')
                ->join('product_batches as pb', 'pb.id', '=', 'cr.product_batch_id')
                ->where('cr.partner_id', $partnerId)
                ->where('cr.status', 'selesai')
                ->whereBetween('cr.created_at', [$periodStart, $periodEnd])
                ->selectRaw('pb.product_id, SUM(cr.terjual) as total_terjual')
                ->groupBy('pb.product_id')
                ->havingRaw('SUM(cr.terjual) > 0')
                ->orderByDesc('total_terjual')
                ->orderBy('pb.product_id')
                ->limit(3)
                ->pluck('pb.product_id');
        }

        return [
            'attiin' => $attiinProductIds
                ->mapWithKeys(fn ($productId, $index): array => [(int) $productId => $index + 1])
                ->all(),
            'mitra' => $mitraProductIds
                ->mapWithKeys(fn ($productId, $index): array => [(int) $productId => $index + 1])
                ->all(),
        ];
    }

    private function attachBestSellerRanks(Product $product, array $ranks): Product
    {
        $product->setAttribute('best_seller_attiin_rank', $ranks['attiin'][$product->id] ?? null);
        $product->setAttribute('best_seller_mitra_rank', $ranks['mitra'][$product->id] ?? null);

        return $product;
    }
}
