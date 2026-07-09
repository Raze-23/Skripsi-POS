<?php

namespace App\Filament\Resources\ProductResource\Actions;

use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class ExportCsvSelectedBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('export_csv_selected')
            ->label('Export CSV')
            ->icon('heroicon-o-document-check')
            ->color('success')
            ->action(function (Collection $records) {
                return response()->streamDownload(function () use ($records) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, ['ID', 'Kode Batch', 'Nama Produk', 'Harga Beli', 'Harga Jual', 'Stok', 'Kedaluwarsa']);
                    foreach ($records as $product) {
                        $product->load('productBatches');
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
