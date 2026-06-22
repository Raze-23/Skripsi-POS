<?php

namespace App\Filament\Resources\ProductResource\Actions;

use App\Models\Product;
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
                $products = Product::select('sku', 'nama')->get();

                // Ubah target view menjadi pdf.qrcodes
                $pdf = Pdf::loadView('pdf.qrcodes', compact('products'));

                // Opsional: Atur ukuran kertas ke A4
                $pdf->setPaper('A4', 'portrait');

                // Ubah nama file unduhan
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'Daftar-QRCode-Produk.pdf'
                );
            });
    }
}
