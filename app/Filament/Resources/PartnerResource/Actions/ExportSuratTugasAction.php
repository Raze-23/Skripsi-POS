<?php

namespace App\Filament\Resources\PartnerResource\Actions;

use App\Models\ConsignmentStock;
use Filament\Actions\Action; 
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;

class ExportSuratTugasAction
{
    public static function make(): Action
    {
        return Action::make('export_surat_tugas')
            ->label('Cetak Surat Penarikan')
            ->icon('heroicon-o-printer')
            ->color('danger')
            ->action(function () {
                
                $totalTitipan = ConsignmentStock::count();
                
                if ($totalTitipan === 0) {
                    Notification::make()
                        ->warning()
                        ->title('Data Kosong')
                        ->body('Belum ada produk yang dititipkan di apotek mana pun saat ini.')
                        ->send();
                    
                    return;
                }

                $stocks = ConsignmentStock::with(['partner', 'productBatch.product'])
                    ->where('stok_titipan', '>', 0)
                    ->whereHas('productBatch', function ($query) {
                        $query->whereNotNull('tanggal_kedaluwarsa')
                              ->whereDate('tanggal_kedaluwarsa', '<=', now()->addDays(30));
                    })
                    ->get();

                if ($stocks->isEmpty()) {
                    Notification::make()
                        ->success()
                        ->title('Aman')
                        ->body('Saat ini tidak ada barang titipan yang masa kedaluwarsanya kritis.')
                        ->send();
                    
                    return;
                }

                $stocks = $stocks->groupBy('partner.nama_apotek');

                $pdf = Pdf::loadView('pdf.surat-tugas', compact('stocks'));
                $pdf->setPaper('A4', 'portrait');

                $namaFile = 'Surat-Penarikan-' . now()->translatedFormat('d-M-Y') . '.pdf';
                
                return response()->streamDownload(
                    fn() => print($pdf->output()),
                    $namaFile
                );
            });
    }
}