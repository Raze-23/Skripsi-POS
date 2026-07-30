<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pendapatan Per Bulan';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $year = (int) ($this->filters['year'] ?? now()->year);
        $kasirPerMonth = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->where('transactions.status', 'Selesai')
            ->whereYear('transactions.created_at', $year)
            ->select(
                DB::raw('MONTH(transactions.created_at) as bulan'),
                DB::raw('SUM(transaction_details.qty * products.harga_jual) as total')
            )
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $apotekPerMonth = DB::table('consignment_returns')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_returns.product_batch_id')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereYear('consignment_returns.created_at', $year)
            ->select(
                DB::raw('MONTH(consignment_returns.created_at) as bulan'),
                DB::raw('SUM(consignment_returns.terjual * products.harga_jual) as total')
            )
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labels = [];
        $data = [];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::createFromDate($year, $m, 1)->translatedFormat('M');
            $data[] = (int) ($kasirPerMonth[$m] ?? 0) + (int) ($apotekPerMonth[$m] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pendapatan (Rp)',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
