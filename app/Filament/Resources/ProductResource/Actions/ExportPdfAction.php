<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\Product;
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
                $products = Product::where('stok_toko', '>', 0)->orderBy('nama', 'asc')->get();
                $pdf = Pdf::loadView('pdf.catalog', compact('products'));
                return response()->streamDownload(fn () => print($pdf->output()), 'Daftar-Produk.pdf');
            });
    }
}
