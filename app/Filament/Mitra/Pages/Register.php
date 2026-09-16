<?php

namespace App\Filament\Mitra\Pages;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Events\Auth\Registered;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Register extends BaseRegister
{
    public function getTitle(): string|Htmlable
    {
        return 'Daftar Akun Mitra';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Daftar Akun Mitra';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Buat akun Anda sendiri di sini. Akun baru akan berstatus "Menunggu Konfirmasi" dan baru bisa dipakai setelah Admin mengaitkannya dengan data apotek Anda.';
    }

    /**
     * Setiap pendaftaran lewat halaman ini otomatis diberi role "mitra"
     * dan status "menunggu_konfirmasi". partner_id sengaja dikosongkan —
     * itu baru diisi Admin lewat menu Kelola Pengguna.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data['role'] = 'mitra';
        $data['status'] = 'menunggu_konfirmasi';
        $data['partner_id'] = null;

        return $data;
    }

    protected function handleRegistration(array $data): Model
    {
        return User::create($data);
    }

    /**
     * Di-override total dari bawaan Filament supaya user TIDAK langsung
     * di-login otomatis setelah daftar (karena akunnya masih menunggu
     * konfirmasi), dan diarahkan kembali ke halaman login dengan notifikasi
     * yang jelas.
     */
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        Notification::make()
            ->success()
            ->title('Pendaftaran Berhasil')
            ->body('Akun Anda telah dibuat. Silakan tunggu konfirmasi dari Admin sebelum bisa masuk ke Portal Mitra.')
            ->persistent()
            ->send();

        return new class implements RegistrationResponse
        {
            public function toResponse($request)
            {
                return redirect()->to(Filament::getLoginUrl());
            }
        };
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title('Terlalu Banyak Percobaan')
            ->body("Silakan coba lagi dalam {$exception->secondsUntilAvailable} detik.")
            ->danger();
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Nama Lengkap')
            ->prefixIcon('heroicon-o-user')
            ->placeholder('Nama apotek')
            ->required()
            ->maxLength(255)
            ->autofocus()
            ->validationMessages([
                'required' => 'Nama wajib diisi.',
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->prefixIcon('heroicon-o-at-symbol')
            ->placeholder('Masukkan alamat email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel())
            ->validationMessages([
                'required' => 'Email wajib diisi.',
                'email' => 'Format email tidak valid.',
                'unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk ke akun Anda.',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->prefixIcon('heroicon-o-key')
            ->placeholder('Minimal 8 karakter')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationMessages([
                'required' => 'Password wajib diisi.',
            ]);
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Konfirmasi Password')
            ->prefixIcon('heroicon-o-lock-closed')
            ->placeholder('Ulangi password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false)
            ->validationMessages([
                'required' => 'Konfirmasi password wajib diisi.',
                'same' => 'Konfirmasi password tidak cocok.',
            ]);
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Masuk ke akun yang sudah ada')
            ->url(filament()->getLoginUrl());
    }

    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label('Kirim Pendaftaran')
            ->icon('heroicon-o-paper-airplane');
    }
}
