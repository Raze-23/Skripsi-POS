<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus Pengguna')
                ->icon('heroicon-o-trash') // Penambahan ikon
                ->color('danger')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Akun Terhapus')
                        ->body('Data akun ini telah berhasil dihapus.')
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
            ->body('Data pengguna telah berhasil diperbarui.');
    }
}