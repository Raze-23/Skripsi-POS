<?php

namespace App\Filament\Mitra\Resources\ProductRequestResource\Pages;

use App\Filament\Mitra\Resources\ProductRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListProductRequests extends ListRecords
{
    protected static string $resource = ProductRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Request Produk')
                ->icon('heroicon-o-plus'),
        ];
    }
}
