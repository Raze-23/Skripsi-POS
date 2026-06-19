<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    #[Override]
    protected function getFormActions(): array
    {
        return [
            $this->getCreateAnotherFormAction()
                ->label('Simpan Apotek')
                ->icon('heroicon-o-check-circle')
                ->color('primary'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->color('gray'),
        ];
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    #[Override]
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Apotek Tersimpan!')
            ->body('Data apotek berhasil ditambahkan dan disimpan.');
    }

    
}
