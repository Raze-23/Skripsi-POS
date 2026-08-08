<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\ProductBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class ExportPdfAction
{
    public static function make(): Action
    {
        return Action::make('export_pdf_catalog')
            ->label('Cetak Katalog')
            ->icon('heroicon-o-document-plus')
            ->color('danger')
            ->action(function () {
                $batches = ProductBatch::where('stok_toko', '>', 0)
                    ->with('product')
                    ->orderBy('product_id')
                    ->get();

                if ($batches->isEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title('Tidak Ada Stok Produk')
                        ->body('Belum ada produk dengan stok tersedia untuk dicetak katalognya.')
                        ->send();

                    return;
                }

                $pdf = Pdf::loadView('pdf.catalog', compact('batches'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'Daftar-Produk-' . now()->format('d-m-Y') . '.pdf'
                );
            });
    }
}