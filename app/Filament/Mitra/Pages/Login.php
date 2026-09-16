<?php

namespace App\Filament\Mitra\Pages;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseAuthLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseAuthLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Masuk Portal Mitra';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Portal Mitra';
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'data.email' => 'Email ini tidak terdaftar dalam sistem.',
            ]);
        }

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.password' => 'Password yang Anda masukkan salah.',
            ]);
        }

        if ($user->role !== 'mitra') {
            throw ValidationException::withMessages([
                'data.email' => 'Akun ini tidak memiliki akses ke Portal Mitra.',
            ]);
        }

        if ($user->status === 'menunggu_konfirmasi') {
            return $this->rejectMitraLogin(
                title: 'Akun sedang menunggu konfirmasi',
                body: 'Silakan coba masuk kembali setelah akun dikonfirmasi.',
                icon: 'heroicon-o-clock',
            );
        }

        if (blank($user->partner_id)) {
            return $this->rejectMitraLogin(
                title: 'Akun belum terhubung ke apotek',
                body: 'Akun mitra Anda sudah terdaftar, tetapi belum dikaitkan dengan data apotek.',
                icon: 'heroicon-o-building-storefront',
            );
        }

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.email' => 'Gagal melakukan autentikasi. Silakan coba lagi.',
            ]);
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => 'Anda tidak memiliki akses ke Portal Mitra.',
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->prefixIcon('heroicon-o-at-symbol')
            ->placeholder('Masukkan alamat email')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->validationMessages([
                'required' => 'Email wajib diisi.',
                'email' => 'Format email tidak valid.',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->prefixIcon('heroicon-o-key')
            ->placeholder('Masukkan password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2])
            ->validationMessages([
                'required' => 'Password wajib diisi.',
            ]);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Login')
            ->icon('heroicon-o-arrow-right-end-on-rectangle');
    }

    public function registerAction(): Action
    {
        return parent::registerAction()
            ->label('Daftar akun mitra');
    }

    private function rejectMitraLogin(
        string $title,
        string $body,
        string $icon,
    ): ?LoginResponse {
        Notification::make()
            ->warning()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->persistent()
            ->send();

        return null;
    }
}
