<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\Action;

class GenerateBarcodeAllAction
{
    public static function make(): Action
    {
        return Action::make('generate_barcode_all')
            ->label('Cetak Barcode')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->action(function () {
                $products = Product::select('sku', 'nama')->get();
                $pdf = Pdf::loadView('pdf.barcodes', compact('products'));
                return response()->streamDownload(fn () => print($pdf->output()), 'Barcode-Produk.pdf');
            });
    }
}
