<?php

namespace App\Filament\Widgets;

use App\Models\Product;
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
        $year = $this->filters['year'] ?? date('Y');
        $topProducts = Product::select('products.nama', DB::raw('SUM(transaction_details.qty) as total_qty'))
            ->join('transaction_details', 'products.id', '=', 'transaction_details.product_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.status', 'Selesai')
            ->whereYear('transactions.created_at', $year)
            ->groupBy('products.id', 'products.nama')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Terjual (Pcs)',
                    'data' => $topProducts->pluck('total_qty')->toArray(),
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
            'labels' => $topProducts->pluck('nama')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; 
    }
}
