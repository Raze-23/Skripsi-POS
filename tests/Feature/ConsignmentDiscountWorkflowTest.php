<?php

namespace Tests\Feature;

use App\Filament\Mitra\Resources\ConsignmentReturnResource\Pages\ManageConsignmentReturns;
use App\Filament\Mitra\Resources\ProductRequestResource\Pages\CreateProductRequest as CreateMitraProductRequest;
use App\Filament\Resources\ProductRequestResource\Pages\CreateProductRequest as CreateOwnerProductRequest;
use App\Filament\Resources\ProductRequestResource\Pages\ListProductRequests;
use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductDisposal;
use App\Models\ProductRequest;
use App\Models\Sales;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ConsignmentDiscountWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_discounted_request_revenue_and_returned_stock_follow_the_consignment_flow(): void
    {
        $partner = Partner::create([
            'nama_apotek' => 'Apotek Test Diskon',
            'alamat' => 'Alamat pengujian',
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-consignment-test@example.com',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $mitra = User::create([
            'name' => 'Mitra Test',
            'email' => 'mitra-consignment-test@example.com',
            'password' => 'password',
            'role' => 'mitra',
            'partner_id' => $partner->id,
            'status' => 'aktif',
        ]);

        $product = Product::create([
            'nama' => 'Produk Test Diskon',
            'harga_beli' => 8_000,
            'harga_jual' => 12_000,
        ]);

        $batch = ProductBatch::create([
            'product_id' => $product->id,
            'batch_code' => 'TEST-DISKON-'.uniqid(),
            'stok_toko' => 100,
            'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
        ]);

        $sales = Sales::create([
            'nama' => 'Sales Test',
            'is_active' => true,
        ]);

        $this->actingAs($mitra);
        Filament::setCurrentPanel(Filament::getPanel('mitra'));

        Livewire::test(CreateMitraProductRequest::class)
            ->set('selectedProductId', $product->id)
            ->set('jumlah', 10)
            ->set('diskonPersen', '20')
            ->call('submit')
            ->assertHasNoErrors();

        $request = ProductRequest::query()
            ->where('user_id', $mitra->id)
            ->where('product_id', $product->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('20.00', $request->diskon_persen);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListProductRequests::class)
            ->callTableAction('proses', $request);

        $request->refresh();
        $this->assertSame('diproses', $request->status);
        $this->assertNull($request->harga_satuan);

        Livewire::test(ListProductRequests::class)
            ->callTableAction('kirim_produk', $request, data: [
                'sales_id' => $sales->id,
                'product_batch_id' => $batch->id,
                'jumlah' => 10,
                'diskon_persen' => 75,
            ])
            ->assertHasNoTableActionErrors();

        $request->refresh();
        $batch->refresh();
        $stock = ConsignmentStock::query()
            ->where('partner_id', $partner->id)
            ->where('product_batch_id', $batch->id)
            ->firstOrFail();

        $this->assertSame('selesai', $request->status);
        $this->assertSame(9_600, $request->harga_satuan);
        $this->assertSame(9_600, $stock->harga_satuan);
        $this->assertSame(90, $batch->stok_toko);

        $return = ConsignmentReturn::create([
            'partner_id' => $partner->id,
            'product_batch_id' => $batch->id,
            'sales_id' => $sales->id,
            'terjual' => 0,
            'qty_layak' => 0,
            'qty_rusak' => 0,
            'diskon_persen' => $stock->diskon_persen,
            'harga_satuan' => $stock->harga_satuan,
            'omzet_terbentuk' => 0,
            'status' => 'menunggu_konfirmasi',
        ]);

        $this->actingAs($mitra);
        Filament::setCurrentPanel(Filament::getPanel('mitra'));

        Livewire::test(ManageConsignmentReturns::class)
            ->callTableAction('isi_rincian', $return, data: [
                'terjual' => 4,
                'qty_layak' => 5,
                'qty_rusak' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $return->refresh();
        $batch->refresh();

        $this->assertSame('selesai', $return->status);
        $this->assertSame(38_400, $return->omzet_terbentuk);
        $this->assertSame(95, $batch->stok_toko);
        $this->assertFalse(ConsignmentStock::whereKey($stock->id)->exists());
        $this->assertSame(1, ProductDisposal::where('consignment_return_id', $return->id)->value('jumlah'));

        $requestTanpaDiskon = ProductRequest::create([
            'user_id' => $mitra->id,
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'tipe_request' => 'restok_apotek',
            'jumlah' => 2,
            'diskon_persen' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListProductRequests::class)
            ->callTableAction('proses', $requestTanpaDiskon);

        $requestTanpaDiskon->refresh();

        Livewire::test(ListProductRequests::class)
            ->callTableAction('kirim_produk', $requestTanpaDiskon, data: [
                'sales_id' => $sales->id,
                'product_batch_id' => $batch->id,
                'jumlah' => 2,
            ])
            ->assertHasNoTableActionErrors();

        $requestTanpaDiskon->refresh();
        $this->assertSame(12_000, $requestTanpaDiskon->harga_satuan);
        $this->assertSame('0.00', $requestTanpaDiskon->diskon_persen);

        $requestDitolak = ProductRequest::create([
            'user_id' => $mitra->id,
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'tipe_request' => 'restok_apotek',
            'jumlah' => 3,
            'diskon_persen' => 5,
            'status' => ProductRequest::STATUS_PENDING,
        ]);

        Livewire::test(ListProductRequests::class)
            ->callTableAction('tolak', $requestDitolak)
            ->assertHasNoTableActionErrors();

        $this->assertSame(ProductRequest::STATUS_DITOLAK, $requestDitolak->refresh()->status);
    }

    public function test_owner_request_has_no_discount_or_delivery_step(): void
    {
        $owner = User::create([
            'name' => 'Owner Workflow Test',
            'email' => 'owner-workflow-test@example.com',
            'password' => 'password',
            'role' => 'owner',
            'status' => 'aktif',
        ]);

        $admin = User::create([
            'name' => 'Admin Owner Workflow Test',
            'email' => 'admin-owner-workflow-test@example.com',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $product = Product::create([
            'nama' => 'Produk Owner Workflow Test',
            'harga_beli' => 5_000,
            'harga_jual' => 9_000,
        ]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateOwnerProductRequest::class)
            ->set('selectedProductId', $product->id)
            ->set('jumlah', 5)
            ->set('diskonPersen', '80')
            ->call('submit')
            ->assertHasNoErrors();

        $request = ProductRequest::where('user_id', $owner->id)->firstOrFail();

        $this->assertSame('produksi_owner', $request->tipe_request);
        $this->assertSame('0.00', $request->diskon_persen);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListProductRequests::class)
            ->callTableAction('proses', $request);

        $request->refresh();

        Livewire::test(ListProductRequests::class)
            ->assertTableActionHidden('kirim_produk', $request)
            ->callTableAction('selesai', $request)
            ->assertHasNoTableActionErrors();

        $this->assertSame(ProductRequest::STATUS_SELESAI, $request->refresh()->status);
    }
}
