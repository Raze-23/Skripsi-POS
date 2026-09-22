<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Welcome extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.welcome';
    
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return '';
    }
    
    public function getOwnerName(): string
    {
        return Auth::user()?->name ?? 'Owner Herbal At-Tiin';
    }
}
