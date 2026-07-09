<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class TopProductsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '5 Produk Terlaris';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '265px';

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

        $kasirSales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->where('transactions.status', 'Selesai')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select('product_batches.product_id', DB::raw('SUM(transaction_details.qty) as total_qty'))
            ->groupBy('product_batches.product_id')
            ->pluck('total_qty', 'product_id');

        $apotekSales = DB::table('consignment_returns')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_returns.product_batch_id')
            ->whereBetween('consignment_returns.created_at', [$startDate, $endDate])
            ->select('product_batches.product_id', DB::raw('SUM(consignment_returns.terjual) as total_qty'))
            ->groupBy('product_batches.product_id')
            ->pluck('total_qty', 'product_id');

        $allProductIds = $kasirSales->keys()->merge($apotekSales->keys())->unique();

        $combinedSales = $allProductIds->map(function ($productId) use ($kasirSales, $apotekSales) {
            return [
                'product_id' => $productId,
                'total_qty'  => $kasirSales->get($productId, 0) + $apotekSales->get($productId, 0),
            ];
        })
        ->sortByDesc('total_qty')
        ->take(5)
        ->values();

        $productIds = $combinedSales->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->pluck('nama', 'id');

        $labels = [];
        $data = [];

        foreach ($combinedSales as $sale) {
            $labels[] = $products->get($sale['product_id']) ?? 'Produk Dihapus';
            $data[] = $sale['total_qty'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Terjual (Pcs)',
                    'data' => $data,
                    'backgroundColor' => [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
