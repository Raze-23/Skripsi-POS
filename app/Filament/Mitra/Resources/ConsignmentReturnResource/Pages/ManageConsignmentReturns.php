<?php

namespace App\Filament\Mitra\Resources\ConsignmentReturnResource\Pages;

use App\Filament\Mitra\Resources\ConsignmentReturnResource;
use Filament\Resources\Pages\ManageRecords;

class ManageConsignmentReturns extends ManageRecords
{
    protected static string $resource = ConsignmentReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
