<?php

namespace Tests\Feature;

use App\Filament\Resources\PartnerResource\Pages\CreatePartner;
use App\Filament\Resources\PartnerResource\Pages\EditPartner;
use App\Models\Partner;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerAdminEditingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_only_edits_partner_name_and_cooperation_date(): void
    {
        $admin = User::create([
            'name' => 'Admin Partner Test',
            'email' => 'admin-partner-form-test@example.com',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'nama_apotek' => 'Apotek Form Test',
                'tanggal_kerja_sama' => '2026-09-16',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $partner = Partner::where('nama_apotek', 'Apotek Form Test')->firstOrFail();

        $this->assertSame('Belum dilengkapi oleh Mitra', $partner->alamat);
        $this->assertNull($partner->no_telp);
        $this->assertTrue($partner->is_active);

        $partner->update([
            'alamat' => 'Jl. Profil Mitra No. 10',
            'no_telp' => '081234567890',
        ]);

        Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()])
            ->assertFormFieldDoesNotExist('alamat')
            ->assertFormFieldDoesNotExist('no_telp')
            ->fillForm([
                'nama_apotek' => 'Apotek Form Test Baru',
                'tanggal_kerja_sama' => '2026-09-20',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $partner->refresh();

        $this->assertSame('Apotek Form Test Baru', $partner->nama_apotek);
        $this->assertSame('2026-09-20', $partner->tanggal_kerja_sama->toDateString());
        $this->assertSame('Jl. Profil Mitra No. 10', $partner->alamat);
        $this->assertSame('081234567890', $partner->no_telp);
    }
}
