<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\ProductBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\Action;

class GenerateQRCodeAllAction
{
    public static function make(): Action
    {
        return Action::make('generate_qrcode_all')
            ->label('Cetak QR Code')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->action(function () {
                $batches = ProductBatch::with('product')->get();
                $pdf = Pdf::loadView('pdf.qrcodes', compact('batches'));
                $pdf->setPaper('A4', 'portrait');
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'Daftar-QRCode-Batch.pdf'
                );
            });
    }
}
