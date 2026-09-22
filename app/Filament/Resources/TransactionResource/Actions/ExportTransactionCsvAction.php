<?php

namespace App\Filament\Resources\TransactionResource\Actions;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Carbon;

class ExportTransactionCsvAction
{
    public static function make(): Action
    {
        return Action::make('export_transaction_csv')
            ->label('Ekspor CSV')
            ->icon('heroicon-o-document-plus')
            ->color('success')
            ->action(function ($livewire) {
                $query = $livewire->getFilteredTableQuery();

                if (! (clone $query)->exists()) {
                    Notification::make()
                        ->warning()
                        ->title('Tidak Ada Data Transaksi')
                        ->body('Tidak ada data transaksi yang sesuai dengan filter saat ini untuk diekspor ke CSV.')
                        ->send();

                    return;
                }

                return response()->streamDownload(function () use ($query) {
                    $file = fopen('php://output', 'w');
                    fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                    fputcsv($file, [
                        'ID',
                        'Waktu Transaksi',
                        'Total Harga (Rp)',
                        'Diskon (%)',
                        'Nominal Bayar (Rp)',
                        'Kembalian (Rp)',
                        'Status'
                    ], ';');

                    $query->chunk(250, function ($transactions) use ($file) {
                        foreach ($transactions as $trx) {
                            fputcsv($file, [
                                $trx->id,
                                Carbon::parse($trx->created_at)->format('Y-m-d H:i:s'),
                                $trx->total_harga,
                                $trx->diskon_persen,
                                $trx->nominal_bayar,
                                $trx->nominal_kembalian,
                                $trx->status,
                            ], ';');
                        }
                    });

                    fclose($file);
                }, 'Riwayat-Transaksi-Kasir-' . now()->format('Y-m-d') . '.csv');
            });
    }
}
