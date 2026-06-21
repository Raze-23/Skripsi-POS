<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\Auth;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
