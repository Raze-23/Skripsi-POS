<?php

namespace App\Filament\Resources\ProductRequestResource\Pages;

use App\Filament\Resources\ProductRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditProductRequest extends EditRecord
{
    protected static string $resource = ProductRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Batalkan Request')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Request Dibatalkan')
                        ->body('Permintaan produk telah berhasil dihapus/dibatalkan.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Perubahan Tersimpan!')
            ->body('Detail request produk telah berhasil diperbarui.');
    }
}