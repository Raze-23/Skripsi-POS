<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\ProductBatch;
use Filament\Tables\Actions\Action;

class ExportCsvAllAction
{
    public static function make(): Action
    {
        return Action::make('export_csv_all')
            ->label('Export CSV')
            ->icon('heroicon-o-document-plus')
            ->color('success')
            ->action(function () {
                return response()->streamDownload(function () {
                    $file = fopen('php://output', 'w');
                    fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    fputcsv($file, ['ID', 'Kode Batch', 'Nama Produk', 'Harga Beli', 'Harga Jual', 'Stok', 'Kedaluwarsa'], ';');
                    ProductBatch::with('product')->chunk(100, function ($batches) use ($file) {
                        foreach ($batches as $b) {
                            fputcsv($file, [
                                $b->id,
                                $b->batch_code,
                                $b->product->nama,
                                $b->product->harga_beli,
                                $b->product->harga_jual,
                                $b->stok_toko,
                                $b->tanggal_kedaluwarsa ? $b->tanggal_kedaluwarsa->format('d-m-Y') : '-',
                            ], ';');
                        }
                    });
                    fclose($file);
                }, 'Semua-Data-Batch-' . now()->format('Y-m-d') . '.csv');
            });
    }
}
