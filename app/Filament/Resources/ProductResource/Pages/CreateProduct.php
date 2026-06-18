<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Tambah Produk Baru';

    #[Override]
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Produk Tersimpan!')
            ->body('Data produk berhasil ditambahkan dan disimpan.');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Produk')
                ->icon('heroicon-o-check-circle'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->color('gray'),
        ];
    }
}
