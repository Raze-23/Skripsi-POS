<?php

namespace App\Filament\Widgets;

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
        $year = (int) ($this->filters['year'] ?? now()->year);

        $formatRupiah = fn (int $value) => 'Rp ' . number_format($value, 0, ',', '.');

        $pendapatanKasir = (int) DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->where('transactions.status', 'Selesai')
            ->whereYear('transactions.created_at', $year)
            ->sum(DB::raw('transaction_details.qty * products.harga_jual'));

        $pendapatanApotek = (int) DB::table('consignment_returns')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_returns.product_batch_id')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereYear('consignment_returns.created_at', $year)
            ->sum(DB::raw('consignment_returns.terjual * products.harga_jual'));

        $totalPendapatan = $pendapatanKasir + $pendapatanApotek;

        $totalPengeluaran = (int) DB::table('product_batches as pb')
            ->join('products as p', 'p.id', '=', 'pb.product_id')
            ->leftJoin(
                DB::raw('(SELECT product_batch_id, COALESCE(SUM(qty), 0) as sold_kasir FROM transaction_details GROUP BY product_batch_id) as td'),
                'td.product_batch_id', '=', 'pb.id'
            )
            ->leftJoin(
                DB::raw('(SELECT product_batch_id, COALESCE(SUM(terjual), 0) as sold_apotek, COALESCE(SUM(qty_rusak), 0) as rusak FROM consignment_returns GROUP BY product_batch_id) as cr'),
                'cr.product_batch_id', '=', 'pb.id'
            )
            ->leftJoin(
                DB::raw('(SELECT product_batch_id, COALESCE(SUM(stok_titipan), 0) as titipan FROM consignment_stocks GROUP BY product_batch_id) as cs'),
                'cs.product_batch_id', '=', 'pb.id'
            )
            ->leftJoin(
                DB::raw('(SELECT product_batch_id, COALESCE(SUM(jumlah), 0) as disposed FROM product_disposals GROUP BY product_batch_id) as pd'),
                'pd.product_batch_id', '=', 'pb.id'
            )
            ->whereYear('pb.created_at', $year)
            ->sum(DB::raw(
                '(pb.stok_toko + COALESCE(td.sold_kasir, 0) + COALESCE(cr.sold_apotek, 0) + COALESCE(cr.rusak, 0) + COALESCE(cs.titipan, 0) + COALESCE(pd.disposed, 0)) * p.harga_beli'
            ));

        $labaBersih = $totalPendapatan - $totalPengeluaran;

        return [
            Stat::make('Total Pendapatan', $formatRupiah($totalPendapatan))
                ->color('success')
                ->description('Total pendapatan tahun ' . $year)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 4, 6, 10, 14, 15, 18]),

            Stat::make('Total Pengeluaran', $formatRupiah($totalPengeluaran))
                ->color('danger')
                ->description('Biaya produksi tahun ' . $year)
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([15, 14, 16, 14, 13, 11, 12]),

            Stat::make('Laba Bersih', $formatRupiah($labaBersih))
                ->color($labaBersih >= 0 ? 'info' : 'danger')
                ->description($labaBersih >= 0 ? 'Untung' : 'Rugi')
                ->descriptionIcon($labaBersih >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-exclamation-triangle')
                ->chart([2, -1, 3, 5, 8, 12, 16]),
        ];
    }
}
