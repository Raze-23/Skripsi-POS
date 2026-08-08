<?php

namespace App\Filament\Resources\ProductResource\Actions;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class ExportCsvSelectedBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('export_csv_selected')
            ->label('Ekspor CSV')
            ->icon('heroicon-o-document-check')
            ->color('success')
            ->action(function (Collection $records) {
                $records->loadMissing('productBatches');

                $hasBatches = $records->contains(fn ($product) => $product->productBatches->isNotEmpty());

                if (! $hasBatches) {
                    Notification::make()
                        ->warning()
                        ->title('Tidak Ada Stok Produk')
                        ->body('Produk yang dipilih belum memiliki data batch untuk diekspor.')
                        ->send();

                    return;
                }

                return response()->streamDownload(function () use ($records) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, ['ID', 'Kode Batch', 'Nama Produk', 'Harga Beli', 'Harga Jual', 'Stok', 'Kedaluwarsa']);
                    foreach ($records as $product) {
                        foreach ($product->productBatches as $b) {
                            fputcsv($file, [
                                $b->id,
                                $b->batch_code,
                                $product->nama,
                                $product->harga_beli,
                                $product->harga_jual,
                                $b->stok_toko,
                                $b->tanggal_kedaluwarsa ? $b->tanggal_kedaluwarsa->format('d-m-Y') : '-',
                            ]);
                        }
                    }
                    fclose($file);
                }, 'Data-Produk-Terpilih-' . now()->format('Y-m-d') . '.csv');
            });
    }
}
