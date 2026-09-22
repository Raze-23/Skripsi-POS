<?php

namespace Tests\Feature;

use App\Filament\Mitra\Resources\ProductRequestResource\Pages\CreateProductRequest as CreateMitraProductRequest;
use App\Filament\Resources\OwnerProductRequestResource\Pages\CreateOwnerProductRequest;
use App\Models\ConsignmentReturn;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductRequest;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProductRequestPresentationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mitra_catalog_has_separate_attiin_and_partner_top_three_rankings(): void
    {
        $this->travelTo(now()->addYears(10));

        $partner = Partner::create([
            'nama_apotek' => 'Apotek Ranking Test',
            'alamat' => 'Alamat test',
            'is_active' => true,
        ]);
        $mitra = $this->createUser('mitra-ranking@example.com', 'mitra', $partner->id);
        $cashier = $this->createUser('cashier-ranking@example.com', 'kasir');
        $products = collect(range(1, 4))->map(fn (int $number) => Product::create([
            'nama' => "Produk Ranking {$number}",
            'harga_beli' => 5_000,
            'harga_jual' => 10_000,
        ]));

        $products->each(function (Product $product, int $index) use ($cashier, $partner): void {
            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'batch_code' => 'RANKING-'.$product->id.'-'.uniqid(),
                'stok_toko' => 20,
                'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
            ]);
            $transaction = Transaction::create([
                'kasir_id' => $cashier->id,
                'total_harga' => 10_000,
                'diskon_persen' => 0,
                'nominal_bayar' => 10_000,
                'nominal_kembalian' => 0,
                'status' => 'Selesai',
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_batch_id' => $batch->id,
                'qty' => 4 - $index,
                'subtotal' => 10_000,
            ]);
            ConsignmentReturn::create([
                'partner_id' => $partner->id,
                'product_batch_id' => $batch->id,
                'terjual' => $index + 1,
                'status' => 'selesai',
            ]);
        });

        $this->actingAs($mitra);
        Filament::setCurrentPanel(Filament::getPanel('mitra'));

        $catalog = Livewire::test(CreateMitraProductRequest::class)
            ->instance()
            ->products()
            ->keyBy('id');

        $this->assertSame(1, $catalog[$products[0]->id]->best_seller_attiin_rank);
        $this->assertSame(2, $catalog[$products[1]->id]->best_seller_attiin_rank);
        $this->assertSame(3, $catalog[$products[2]->id]->best_seller_attiin_rank);
        $this->assertNull($catalog[$products[3]->id]->best_seller_attiin_rank);
        $this->assertSame(1, $catalog[$products[3]->id]->best_seller_mitra_rank);
        $this->assertSame(2, $catalog[$products[2]->id]->best_seller_mitra_rank);
        $this->assertSame(3, $catalog[$products[1]->id]->best_seller_mitra_rank);
        $this->assertNull($catalog[$products[0]->id]->best_seller_mitra_rank);
    }

    public function test_owner_gets_current_month_production_indicators_without_an_automatic_suggestion(): void
    {
        $partner = Partner::create([
            'nama_apotek' => 'Apotek Production Insight',
            'alamat' => 'Alamat test',
            'is_active' => true,
        ]);
        $owner = $this->createUser('owner-insight@example.com', 'owner');
        $mitra = $this->createUser('mitra-insight@example.com', 'mitra', $partner->id);
        $cashier = $this->createUser('cashier-insight@example.com', 'kasir');
        $product = Product::create([
            'nama' => 'Produk Production Insight',
            'harga_beli' => 5_000,
            'harga_jual' => 10_000,
        ]);
        $batch = ProductBatch::create([
            'product_id' => $product->id,
            'batch_code' => 'INSIGHT-'.uniqid(),
            'stok_toko' => 10,
            'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
        ]);
        ProductBatch::create([
            'product_id' => $product->id,
            'batch_code' => 'EXPIRING-'.uniqid(),
            'stok_toko' => 2,
            'tanggal_kedaluwarsa' => now()->addDays(15)->toDateString(),
        ]);
        ProductBatch::create([
            'product_id' => $product->id,
            'batch_code' => 'EXPIRED-'.uniqid(),
            'stok_toko' => 100,
            'tanggal_kedaluwarsa' => now()->subDay()->toDateString(),
        ]);
        $transaction = Transaction::create([
            'kasir_id' => $cashier->id,
            'total_harga' => 40_000,
            'diskon_persen' => 0,
            'nominal_bayar' => 40_000,
            'nominal_kembalian' => 0,
            'status' => 'Selesai',
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_batch_id' => $batch->id,
            'qty' => 4,
            'subtotal' => 40_000,
        ]);
        $previousMonthTransaction = Transaction::create([
            'kasir_id' => $cashier->id,
            'total_harga' => 1_000_000,
            'diskon_persen' => 0,
            'nominal_bayar' => 1_000_000,
            'nominal_kembalian' => 0,
            'status' => 'Selesai',
            'created_at' => now()->subMonthNoOverflow()->startOfMonth(),
            'updated_at' => now()->subMonthNoOverflow()->startOfMonth(),
        ]);
        TransactionDetail::create([
            'transaction_id' => $previousMonthTransaction->id,
            'product_batch_id' => $batch->id,
            'qty' => 100,
            'subtotal' => 1_000_000,
        ]);
        ConsignmentReturn::create([
            'partner_id' => $partner->id,
            'product_batch_id' => $batch->id,
            'terjual' => 3,
            'status' => 'selesai',
        ]);
        ProductRequest::create([
            'user_id' => $mitra->id,
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'tipe_request' => 'restok_apotek',
            'jumlah' => 8,
            'status' => ProductRequest::STATUS_PENDING,
        ]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $component = Livewire::test(CreateOwnerProductRequest::class)
            ->set('selectedProductId', $product->id);
        $selectedProduct = $component->instance()->selectedProduct();

        $this->assertSame(12, (int) $selectedProduct->stok_attiin);
        $this->assertSame(7, $selectedProduct->terjual_bulan_ini);
        $this->assertSame(8, (int) $selectedProduct->kebutuhan_mitra_aktif);
        $this->assertSame(2, (int) $selectedProduct->stok_mendekati_kedaluwarsa);

        $component
            ->assertSee('Terjual bulan ini')
            ->assertSee('ED ≤ 30 hari')
            ->assertDontSee('Saran awal')
            ->assertDontSee('Stok mencukupi')
            ->assertDontSee('Terjual 30h');
    }

    private function createUser(string $email, string $role, ?int $partnerId = null): User
    {
        return User::create([
            'name' => str($role)->headline()->toString().' Test',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'partner_id' => $partnerId,
            'status' => 'aktif',
        ]);
    }
}
