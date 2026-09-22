<?php

namespace App\Filament\Resources\OwnerProductRequestResource\Pages;

use App\Filament\Resources\OwnerProductRequestResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOwnerProductRequest extends EditRecord
{
    protected static string $resource = OwnerProductRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Batalkan Usulan')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Dibatalkan')
                        ->body('Usulan produksi berhasil dihapus/dibatalkan.')
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
            ->body('Usulan produksi berhasil diperbarui.');
    }
}
