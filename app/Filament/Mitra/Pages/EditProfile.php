<?php

namespace App\Filament\Mitra\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * Halaman "Edit Profile" untuk Mitra. Selain nama/email/password akun
 * (bawaan Filament), halaman ini juga menampilkan data apotek yang
 * terkait dengan akun tersebut supaya Mitra bisa memperbarui info
 * apoteknya sendiri (nama apotek, no. telp, alamat) tanpa perlu minta
 * bantuan Admin.
 */
class EditProfile extends BaseEditProfile
{
    protected bool $hasTopbar = false;

    protected array $extraBodyAttributes = ['class' => 'mitra-profile-page'];

    public function getView(): string
    {
        return 'filament.mitra.pages.edit-profile';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Profil Saya';
    }

    public function getMaxWidth(): MaxWidth|string|null
    {
        return MaxWidth::FiveExtraLarge;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Akun Saya')
                    ->description('Identitas dan alamat email akun mitra.')
                    ->icon('heroicon-o-user-circle')
                    ->aside()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                    ])
                    ->columns(2),

                Section::make('Informasi Apotek')
                    ->description('Kontak dan alamat apotek yang terhubung.')
                    ->icon('heroicon-o-building-storefront')
                    ->aside()
                    ->visible(fn (): bool => filled($this->getUser()->partner_id))
                    ->schema([
                        TextInput::make('partner_nama_apotek')
                            ->label('Nama Apotek')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->placeholder('Nama apotek')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('partner_no_telp')
                            ->label('Nomor Telepon')
                            ->prefixIcon('heroicon-o-phone')
                            ->placeholder('Nomor telepon apotek')
                            ->autocomplete('tel')
                            ->tel()
                            ->maxLength(20),

                        Textarea::make('partner_alamat')
                            ->label('Alamat Apotek')
                            ->placeholder('Jalan, nomor, kelurahan, dan kecamatan')
                            ->autocomplete('street-address')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Keamanan Akun')
                    ->description('Kosongkan password jika tidak ingin mengubahnya.')
                    ->icon('heroicon-o-lock-closed')
                    ->aside()
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()
            ->label('Nama Lengkap')
            ->prefixIcon('heroicon-o-user')
            ->placeholder('Nama lengkap')
            ->autocomplete('name');
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->label('Alamat Email')
            ->prefixIcon('heroicon-o-at-symbol')
            ->placeholder('nama@email.com')
            ->autocomplete('email');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Password Baru')
            ->prefixIcon('heroicon-o-key')
            ->placeholder('Password baru');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Konfirmasi Password Baru')
            ->prefixIcon('heroicon-o-lock-closed')
            ->placeholder('Ulangi password')
            ->autocomplete('new-password');
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $partner = $this->getUser()->partner;

        if ($partner) {
            $data['partner_nama_apotek'] = $partner->nama_apotek;
            $data['partner_no_telp'] = $partner->no_telp;
            $data['partner_alamat'] = $partner->alamat;
        }

        return $data;
    }

    /**
     * User (nama/email/password) dan Partner (data apotek) adalah dua
     * tabel berbeda, jadi keduanya di-update terpisah di sini.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $partnerData = [
            'nama_apotek' => $data['partner_nama_apotek'] ?? null,
            'no_telp' => $data['partner_no_telp'] ?? null,
            'alamat' => $data['partner_alamat'] ?? null,
        ];

        unset($data['partner_nama_apotek'], $data['partner_no_telp'], $data['partner_alamat']);

        $record->update($data);

        if ($record->partner) {
            $record->partner->update($partnerData);
        }

        return $record;
    }
}
