<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\Product;
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
                    fputcsv($file, ['ID', 'SKU', 'Nama Produk', 'Harga Beli', 'Harga Jual', 'Stok', 'Kedaluwarsa'], ';');
                    Product::chunk(100, function ($products) use ($file) {
                        foreach ($products as $p) {
                            fputcsv($file, [
                                $p->id,
                                $p->sku,
                                $p->nama,
                                $p->harga_beli,
                                $p->harga_jual,
                                $p->stok_toko,
                                $p->tanggal_kedaluwarsa->format('d-m-Y')
                            ], ';');
                        }
                    });
                    fclose($file);
                }, 'Semua-Data-Produk-' . now()->format('Y-m-d') . '.csv');
            });
    }
}
