<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use App\Http\Responses\LoginResponse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            LoginResponseContract::class,
            LoginResponse::class
        );
    }

    public function boot(): void
    {
    }
}
