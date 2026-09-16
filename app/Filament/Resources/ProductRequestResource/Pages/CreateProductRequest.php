<?php

namespace App\Filament\Resources\ProductRequestResource\Pages;

use App\Filament\Resources\ProductRequestResource;
use App\Models\Product;
use App\Models\ProductRequest;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class CreateProductRequest extends Page
{
    protected static string $resource = ProductRequestResource::class;

    protected static string $view = 'filament.resources.product-request-resource.pages.create-product-request';

    protected static ?string $title = 'Buat Request Produk';

    public string $search = '';

    public ?int $selectedProductId = null;

    public int $jumlah = 1;

    public ?string $diskonPersen = null;

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
            ->select(['id', 'nama', 'foto', 'harga_jual'])
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

        return Product::find($this->selectedProductId);
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
            'diskonPersen' => $user?->role === 'mitra'
                ? ['nullable', 'numeric', 'min:0', 'max:100']
                : ['nullable'],
        ], [
            'selectedProductId.required' => 'Pilih produk terlebih dahulu.',
            'selectedProductId.exists' => 'Produk tidak valid.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1 pcs.',
            'diskonPersen.numeric' => 'Diskon harus berupa angka.',
            'diskonPersen.min' => 'Diskon tidak boleh kurang dari 0%.',
            'diskonPersen.max' => 'Diskon tidak boleh lebih dari 100%.',
        ]);

        ProductRequest::create([
            'user_id' => $user->id,
            'partner_id' => $user->role === 'mitra' ? $user->partner_id : null,
            'product_id' => $this->selectedProductId,
            'tipe_request' => match ($user->role) {
                'owner' => 'produksi_owner',
                'mitra' => 'restok_apotek',
                default => 'produksi_owner',
            },
            'jumlah' => $this->jumlah,
            'diskon_persen' => $user->role === 'mitra'
                ? round((float) ($this->diskonPersen ?? 0), 2)
                : 0,
            'status' => ProductRequest::STATUS_PENDING,
        ]);

        Notification::make()
            ->success()
            ->title('Request Berhasil Dibuat!')
            ->body($user->role === 'mitra'
                ? 'Permintaan produk dan diskon Anda telah masuk dan menunggu keputusan Admin.'
                : 'Permintaan produksi Anda telah masuk ke sistem dan menunggu proses Admin.')
            ->send();

        $this->redirect(static::getResource()::getUrl('index'));
    }
}
