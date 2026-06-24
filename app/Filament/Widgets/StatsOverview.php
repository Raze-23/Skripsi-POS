<?php

namespace App\Filament\Widgets;

use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $year = $this->filters['year'] ?? date('Y');

        // =========================================================
        // 1. HITUNG PEMASUKAN (LABA KOTOR)
        // =========================================================
        $pemasukanKasir = Transaction::where('status', 'Selesai')
            ->whereYear('created_at', $year)
            ->sum('total_harga');

        $pemasukanApotek = ConsignmentReturn::whereYear('created_at', $year)
            ->with('product')
            ->get()
            ->sum(fn ($return) => $return->terjual * ($return->product->harga_jual ?? 0));

        $pemasukan = $pemasukanKasir + $pemasukanApotek;

        // =========================================================
        // 2. HITUNG PENGELUARAN (MODAL KESELURUHAN)
        // =========================================================
        $modalKasirTerjual = Transaction::where('status', 'Selesai')
            ->whereYear('created_at', $year)
            ->with('details.product')
            ->get()
            ->flatMap->details->sum(fn ($detail) => $detail->qty * ($detail->product->harga_beli ?? 0));

        $modalApotekTerjual = ConsignmentReturn::whereYear('created_at', $year)
            ->with('product')
            ->get()
            ->sum(fn ($return) => $return->terjual * ($return->product->harga_beli ?? 0));

        $totalModalTerjual = $modalKasirTerjual + $modalApotekTerjual;

        if ($year == date('Y')) {
            $modalStokToko = Product::sum(DB::raw('stok_toko * harga_beli'));
            $modalStokTitipan = ConsignmentStock::join('products', 'consignment_stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('consignment_stocks.stok_titipan * products.harga_beli'));

            $modalSisaStok = $modalStokToko + $modalStokTitipan;

            $pengeluaran = $totalModalTerjual + $modalSisaStok;
        } else {
            $pengeluaran = $totalModalTerjual;
        }

        // =========================================================
        // 3. HITUNG LABA BERSIH
        // =========================================================
        $profit = $pemasukan - $pengeluaran;

        return [
            Stat::make('Laba Kotor ', 'Rp ' . number_format($pemasukan, 0, ',', '.'))
                ->color('success')
                ->description('Total omzet')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 4, 6, 10, 14, 15, 18]),

            Stat::make('Pengeluaran ', 'Rp ' . number_format($pengeluaran, 0, ',', '.'))
                ->color('danger')
                ->description('Total modal')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([15, 14, 16, 14, 13, 11, 12]),

            Stat::make('Laba Bersih ', 'Rp ' . number_format($profit, 0, ',', '.'))
                ->color($profit >= 0 ? 'info' : 'danger')
                ->description($profit >= 0 ? 'Untung' : 'Defisit')
                ->descriptionIcon($profit >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-exclamation-triangle')
                ->chart([2, -1, 3, 5, 8, 12, 16]),
        ];
    }
}
