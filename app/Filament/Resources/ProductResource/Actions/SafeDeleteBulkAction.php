<?php

namespace App\Filament\Resources\ProductResource\Actions;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;

class SafeDeleteBulkAction
{
    public static function make(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->before(function (Collection $records, DeleteBulkAction $action) {
                // Cek apakah di antara produk yang dipilih ada yang sudah memiliki batch
                $hasBatches = $records->filter(fn ($record) => $record->productBatches()->exists())->isNotEmpty();

                if ($hasBatches) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal Menghapus Massal')
                        ->body('Satu atau lebih produk yang Anda pilih tidak dapat dihapus karena masih memiliki riwayat Batch Produk yang terikat.')
                        ->send();
                    
                    $action->halt();
                }
            });
    }
}