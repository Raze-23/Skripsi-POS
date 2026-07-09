<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\ProductBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\Action;

class ExportPdfAction
{
    public static function make(): Action
    {
        return Action::make('export_pdf_catalog')
            ->label('Cetak PDF')
            ->icon('heroicon-o-document-plus')
            ->color('danger')
            ->action(function () {
                $batches = ProductBatch::where('stok_toko', '>', 0)
                    ->with('product')
                    ->orderBy('product_id')
                    ->get();
                $pdf = Pdf::loadView('pdf.catalog', compact('batches'));
                return response()->streamDownload(fn () => print($pdf->output()), 'Daftar-Produk.pdf');
            });
    }
}
