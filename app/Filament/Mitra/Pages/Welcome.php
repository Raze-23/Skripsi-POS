<?php

namespace App\Filament\Mitra\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Welcome extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.mitra.pages.welcome';
    
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return '';
    }

    public function getApotekName(): string
    {
        return Auth::user()?->partner?->nama_apotek ?? 'Mitra Herbal At-Tiin';
    }
}
