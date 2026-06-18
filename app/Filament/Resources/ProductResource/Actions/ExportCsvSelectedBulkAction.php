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
                    fputcsv($file, ['ID', 'SKU', 'Nama Produk', 'Harga Beli', 'Harga Jual', 'Stok', 'Kedaluwarsa']);

                    foreach ($records as $p) {
                        fputcsv($file, [$p->id, $p->sku, $p->nama, $p->harga_beli, $p->harga_jual, $p->stok_toko, $p->tanggal_kedaluwarsa->format('d-m-Y')]);
                    }
                    fclose($file);
                }, 'Data-Produk-Terpilih-' . now()->format('Y-m-d') . '.csv');
            });
    }
}
