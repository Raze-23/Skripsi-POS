<?php

namespace App\Filament\Resources\ProductRequestResource\Pages;

use App\Filament\Resources\ProductRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProductRequests extends ListRecords
{
    protected static string $resource = ProductRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(fn () => Auth::user()?->role === 'owner' ? 'Buat Usulan Produksi' : 'Buat Request Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),
        ];
    }
}
