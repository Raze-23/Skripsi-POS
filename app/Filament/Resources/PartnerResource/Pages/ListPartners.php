<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource\Actions\ExportSuratTugasAction;
use App\Filament\Resources\PartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportSuratTugasAction::make(),

            Actions\CreateAction::make()
            ->label('Tambah Mitra')
            ->icon('heroicon-o-plus-circle')
        ];
    }
}
