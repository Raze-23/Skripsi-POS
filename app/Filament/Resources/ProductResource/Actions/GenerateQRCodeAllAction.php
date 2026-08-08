<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\ProductBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
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
                $batches = ProductBatch::with('product')
                    ->orderByDesc('created_at')
                    ->get();

                if ($batches->isEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title('Tidak Ada Stok Produk')
                        ->body('Belum ada data batch produk untuk dicetak QR Code-nya.')
                        ->send();

                    return;
                }

                $pdf = Pdf::loadView('pdf.qrcodes', compact('batches'));
                $pdf->setPaper('A4', 'portrait');
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'Daftar-QRCode-Batch-' . now()->format('d-m-Y') . '.pdf'
                );
            });
    }
}