<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class DailySalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Total Penjualan Perhari';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
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

        $soldProducts = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'Selesai')
            ->select('products.id', 'products.nama')
            ->groupBy('products.id', 'products.nama')
            ->get();

        if ($soldProducts->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }

        $soldProductIds = $soldProducts->pluck('id')->toArray();

        $dailySales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->whereIn('product_batches.product_id', $soldProductIds)
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'Selesai')
            ->select(
                'product_batches.product_id',
                DB::raw('DATE(transactions.created_at) as date'),
                DB::raw('SUM(transaction_details.qty) as daily_qty')
            )
            ->groupBy('product_batches.product_id', 'date')
            ->get();

        $labels = [];
        $days = [];
        $totalDays = (int) $startDate->copy()->diffInDays($endDate->copy()->startOfDay()) + 1;

        for ($i = 0; $i < $totalDays; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->translatedFormat('d M');
            $days[] = $date;
        }

        $datasets = [];

        foreach ($soldProducts as $index => $product) {
            $data = [];
            foreach ($days as $day) {
                $sale = $dailySales->where('product_id', $product->id)->where('date', $day)->first();
                $data[] = $sale ? (int) $sale->daily_qty : 0;
            }

            $hue = ($index * 137.508) % 360;
            $dynamicColor = "hsl({$hue}, 70%, 50%)";

            $datasets[] = [
                'label' => $product->nama,
                'data' => $data,
                'borderColor' => $dynamicColor,
                'backgroundColor' => $dynamicColor,
                'borderWidth' => 2,
                'tension' => 0.4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
