<?php

namespace Tests\Feature;

use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\Partner;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductDisposal;
use App\Models\User;
use App\Services\ConsignmentReturnPaymentService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ConsignmentPaymentValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_rejection_keeps_stock_untouched_and_allows_a_new_proof(): void
    {
        [$return, $stock, $batch, $mitra, $admin, $account] = $this->fixture();
        $service = app(ConsignmentReturnPaymentService::class);
        Storage::fake('local');
        Storage::disk('local')->put("bukti-pembayaran-retur/{$return->id}/first.png", 'first');

        $service->submit($return, $mitra, $this->transferData($account, $return, 'first.png'));
        $this->assertSame('menunggu_validasi', $return->refresh()->status);
        $this->assertSame(10, $stock->refresh()->stok_titipan);
        $this->assertSame(90, $batch->refresh()->stok_toko);
        $this->actingAs($mitra)->get(route('consignment-payment-proof.show', $return))->assertOk();
        $this->actingAs($admin)->get(route('consignment-payment-proof.show', $return))->assertOk();

        $service->reject($return, $admin, 'Jumlah pada mutasi rekening tidak sesuai.');
        $this->assertSame('menunggu_konfirmasi', $return->refresh()->status);
        $this->assertSame('Jumlah pada mutasi rekening tidak sesuai.', $return->catatan_penolakan);
        $this->assertNull($return->bukti_pembayaran);
        $this->assertTrue(ConsignmentStock::whereKey($stock->id)->exists());

        Storage::disk('local')->put("bukti-pembayaran-retur/{$return->id}/second.png", 'second');
        $service->submit($return, $mitra, $this->transferData($account, $return, 'second.png'));
        $this->assertNull($return->refresh()->catatan_penolakan);
        $service->approve($return, $admin);
        $this->assertSame('selesai', $return->refresh()->status);
        $this->assertSame(95, $batch->refresh()->stok_toko);
        $this->assertFalse(ConsignmentStock::whereKey($stock->id)->exists());
        $this->assertSame(1, ProductDisposal::where('consignment_return_id', $return->id)->count());
        $this->assertSame($account->account_number, $return->rekening_tujuan);

        try {
            $service->approve($return, $admin);
            $this->fail('Validasi kedua seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertSame(95, $batch->refresh()->stok_toko);
        }
    }

    public function test_return_without_sales_finishes_without_payment_and_proof_is_private(): void
    {
        [$return, $stock, $batch, $mitra, $admin] = $this->fixture();
        app(ConsignmentReturnPaymentService::class)->submit($return, $mitra, [
            'terjual' => 0, 'qty_layak' => 10, 'qty_rusak' => 0,
        ]);
        $this->assertSame('selesai', $return->refresh()->status);
        $this->assertSame(100, $batch->refresh()->stok_toko);
        $this->assertFalse(ConsignmentStock::whereKey($stock->id)->exists());
        $this->assertNull($return->metode_pembayaran);

        $this->actingAs($mitra)->get(route('consignment-payment-proof.show', $return))->assertNotFound();
        $this->actingAs($admin)->get(route('consignment-payment-proof.show', $return))->assertNotFound();
    }

    public function test_cash_payment_waits_for_admin_and_cannot_be_submitted_by_another_partner(): void
    {
        [$return, $stock, $batch, $mitra, $admin] = $this->fixture();
        $service = app(ConsignmentReturnPaymentService::class);
        $other = User::create(['name' => 'Mitra Lain', 'email' => uniqid('other-').'@example.com',
            'password' => 'password', 'role' => 'mitra', 'partner_id' => Partner::create([
                'nama_apotek' => 'Apotek Lain '.uniqid(), 'alamat' => 'Test', 'is_active' => true,
            ])->id, 'status' => 'aktif']);
        try {
            $service->submit($return, $other, ['terjual' => 4, 'qty_layak' => 6, 'qty_rusak' => 0,
                'metode_pembayaran' => 'tunai_sales']);
            $this->fail('Mitra lain tidak boleh mengisi retur.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->actingAs($other)->get(route('consignment-payment-proof.show', $return))->assertForbidden();

        $service->submit($return, $mitra, ['terjual' => 4, 'qty_layak' => 6, 'qty_rusak' => 0,
            'metode_pembayaran' => 'tunai_sales']);
        $this->assertSame('menunggu_validasi', $return->refresh()->status);
        $this->assertNull($return->bukti_pembayaran);
        $this->assertSame(90, $batch->refresh()->stok_toko);
        $this->assertTrue(ConsignmentStock::whereKey($stock->id)->exists());
        $service->approve($return, $admin);
        $this->assertSame('selesai', $return->refresh()->status);
        $this->assertSame(96, $batch->refresh()->stok_toko);
    }

    public function test_admin_manages_transfer_accounts_from_partner_list_modal(): void
    {
        [, , , , $admin, $account] = $this->fixture();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(\App\Filament\Resources\PartnerResource\Pages\ListPartners::class)
            ->callAction('rekening_pembayaran', data: ['rekening' => [
                ['id' => $account->id, 'bank' => 'Bank Tujuan', 'account_number' => '001234567890',
                    'account_name' => 'CV Test', 'is_active' => false],
                ['bank' => 'Bank Baru', 'account_number' => '000987654321',
                    'account_name' => 'CV Test', 'is_active' => true],
            ]])->assertHasNoActionErrors();

        $this->assertSame('001234567890', $account->refresh()->account_number);
        $this->assertFalse($account->is_active);
        $this->assertTrue(PaymentAccount::where('account_number', '000987654321')->where('is_active', true)->exists());
    }

    private function fixture(): array
    {
        $partner = Partner::create(['nama_apotek' => 'Apotek Validasi '.uniqid(), 'alamat' => 'Test', 'is_active' => true]);
        $product = Product::create(['nama' => 'Produk Validasi', 'harga_beli' => 7000, 'harga_jual' => 10000]);
        $batch = ProductBatch::create(['product_id' => $product->id, 'batch_code' => 'PAY-'.uniqid(),
            'stok_toko' => 90, 'tanggal_kedaluwarsa' => now()->addYear()->toDateString()]);
        $stock = ConsignmentStock::create(['partner_id' => $partner->id, 'product_batch_id' => $batch->id,
            'stok_titipan' => 10, 'harga_satuan' => 9000, 'diskon_persen' => 10]);
        $mitra = User::create(['name' => 'Mitra Payment', 'email' => uniqid('mitra-').'@example.com',
            'password' => 'password', 'role' => 'mitra', 'partner_id' => $partner->id, 'status' => 'aktif']);
        $admin = User::create(['name' => 'Admin Payment', 'email' => uniqid('admin-').'@example.com',
            'password' => 'password', 'role' => 'admin', 'status' => 'aktif']);
        $account = PaymentAccount::create(['bank' => 'Bank Tujuan', 'account_number' => '123456',
            'account_name' => 'CV Test', 'is_active' => true]);
        $return = ConsignmentReturn::create(['partner_id' => $partner->id, 'product_batch_id' => $batch->id,
            'terjual' => 0, 'qty_layak' => 0, 'qty_rusak' => 0, 'harga_satuan' => 9000,
            'omzet_terbentuk' => 0, 'status' => 'menunggu_konfirmasi']);
        return [$return, $stock, $batch, $mitra, $admin, $account];
    }

    private function transferData(PaymentAccount $account, ConsignmentReturn $return, string $file): array
    {
        return ['terjual' => 4, 'qty_layak' => 5, 'qty_rusak' => 1,
            'metode_pembayaran' => 'transfer_bank', 'payment_account_id' => $account->id,
            'nama_pengirim' => 'Apotek Validasi', 'bank_pengirim' => 'Bank Asal',
            'dibayar_pada' => now()->subMinute()->format('Y-m-d H:i:s'),
            'bukti_pembayaran' => "bukti-pembayaran-retur/{$return->id}/".$file];
    }
}
