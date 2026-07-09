<?php

namespace App\Filament\Widgets;

use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\ProductBatch;
use App\Models\Transaction;
use Carbon\Carbon;
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
        $periode = $this->filters['periode'] ?? 'hari_ini';
        $startDate = now()->startOfDay();
        $endDate = now()->endOfDay();

        if ($periode === 'minggu_ini') {
            $startDate = now()->subDays(7)->startOfDay();
        } elseif ($periode === 'bulan_ini') {
            $startDate = now()->startOfMonth();
        } elseif ($periode === 'tahun_ini') {
            $startDate = now()->startOfYear();
        } elseif ($periode === 'kustom') {
            $startDate = Carbon::parse($this->filters['start_date'] ?? now())->startOfDay();
            $endDate = Carbon::parse($this->filters['end_date'] ?? now())->endOfDay();
        }

        $pemasukanKasir = Transaction::where('status', 'Selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_harga');

        $pemasukanApotek = ConsignmentReturn::whereBetween('created_at', [$startDate, $endDate])
            ->with('productBatch.product')
            ->get()
            ->sum(fn ($return) => $return->terjual * ($return->productBatch->product->harga_jual ?? 0));

        $pemasukan = $pemasukanKasir + $pemasukanApotek;

        $modalKasirTerjual = Transaction::where('status', 'Selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('details.productBatch.product')
            ->get()
            ->flatMap->details->sum(fn ($detail) => $detail->qty * ($detail->productBatch->product->harga_beli ?? 0));

        $modalApotekTerjual = ConsignmentReturn::whereBetween('created_at', [$startDate, $endDate])
            ->with('productBatch.product')
            ->get()
            ->sum(fn ($return) => $return->terjual * ($return->productBatch->product->harga_beli ?? 0));

        $totalModalTerjual = $modalKasirTerjual + $modalApotekTerjual;

        $mencakupHariIni = now()->between($startDate, $endDate);

        if ($mencakupHariIni) {
            $modalStokToko = ProductBatch::join('products', 'product_batches.product_id', '=', 'products.id')
                ->sum(DB::raw('product_batches.stok_toko * products.harga_beli'));

            $modalStokTitipan = ConsignmentStock::join('product_batches', 'consignment_stocks.product_batch_id', '=', 'product_batches.id')
                ->join('products', 'product_batches.product_id', '=', 'products.id')
                ->sum(DB::raw('consignment_stocks.stok_titipan * products.harga_beli'));

            $modalSisaStok = $modalStokToko + $modalStokTitipan;

            $pengeluaran = $totalModalTerjual + $modalSisaStok;
        } else {
            $pengeluaran = $totalModalTerjual;
        }

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
