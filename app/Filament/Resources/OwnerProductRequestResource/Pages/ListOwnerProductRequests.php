<?php

namespace App\Filament\Resources\OwnerProductRequestResource\Pages;

use App\Filament\Resources\OwnerProductRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwnerProductRequests extends ListRecords
{
    protected static string $resource = OwnerProductRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Usulan Produksi')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),
        ];
    }
}
