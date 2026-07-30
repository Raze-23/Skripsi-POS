<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Auth::user();

        if ($user && $user->role === 'kasir') {
           
            return redirect()->to('/sales/cashier'); 
        }

        return redirect()->intended(filament()->getUrl());
    }
}