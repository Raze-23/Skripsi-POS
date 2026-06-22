<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login as BaseAuthLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseAuthLogin
{
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'Email atau Password yang Anda masukkan salah!',
        ]);
    }
}
