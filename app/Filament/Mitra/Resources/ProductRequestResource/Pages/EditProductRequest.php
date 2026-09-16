<?php

namespace App\Filament\Mitra\Resources\ProductRequestResource\Pages;

use App\Filament\Mitra\Resources\ProductRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditProductRequest extends EditRecord
{
    protected static string $resource = ProductRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Request produk berhasil diperbarui!';
    }
}
