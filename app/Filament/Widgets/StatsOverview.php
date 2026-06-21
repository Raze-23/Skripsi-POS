<?php

namespace App\Filament\Widgets;

use App\Models\Product; // Wajib di-import untuk mengambil data modal
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB; // Wajib di-import untuk fungsi DB::raw

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $pemasukan = Transaction::where('status', 'Selesai')->sum('total_harga');
        $pengeluaran = Product::sum(DB::raw('stok_toko * harga_beli'));
        $profit = $pemasukan - $pengeluaran;

        return [
            // KOTAK 1: PEMASUKAN
            Stat::make('Pemasukan', 'Rp ' . number_format($pemasukan, 0, ',', '.'))
                ->description('Total omset')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 10, 5, 15, 8, 20]),

            // KOTAK 2: PENGELUARAN (Total Modal)
            Stat::make('Pengeluaran', 'Rp ' . number_format($pengeluaran, 0, ',', '.'))
                ->description('Total jumlah modal seluruh stok toko')
                ->descriptionIcon('heroicon-m-cube')
                ->color('danger')
                ->chart([3, 2, 5, 1, 4, 2, 3]),

            // KOTAK 3: PROFIT
            Stat::make('Profit vs Modal', 'Rp ' . number_format($profit, 0, ',', '.'))
                ->description('Selisih antara omset dan total pengeluaran')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($profit >= 0 ? 'primary' : 'danger')
                ->chart([4, 1, 5, 4, 11, 6, 17]),
        ];
    }
}
