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

        // 1. Rekap jumlah qty terjual dari Mesin Kasir per Produk
        $kasirSales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.status', 'Selesai')
            ->whereYear('transactions.created_at', $year)
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id'); // Menghasilkan format: [product_id => total_qty]

        // 2. Rekap jumlah barang terjual dari Apotek (Konsinyasi) per Produk
        $apotekSales = DB::table('consignment_returns')
            ->whereYear('created_at', $year)
            ->select('product_id', DB::raw('SUM(terjual) as total_qty'))
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        // 3. Gabungkan semua ID Produk dari kedua sumber tanpa ada duplikat
        $allProductIds = $kasirSales->keys()->merge($apotekSales->keys())->unique();

        // 4. Kalkulasi total gabungan, urutkan dari yang terbanyak, dan ambil 5 teratas
        $combinedSales = $allProductIds->map(function ($productId) use ($kasirSales, $apotekSales) {
            return [
                'product_id' => $productId,
                'total_qty'  => $kasirSales->get($productId, 0) + $apotekSales->get($productId, 0),
            ];
        })
        ->sortByDesc('total_qty')
        ->take(5)
        ->values(); // Mereset urutan index array

        // 5. Ambil nama produk dari database untuk dijadikan Label pada Chart
        $productIds = $combinedSales->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->pluck('nama', 'id');

        // 6. Pisahkan antara Label (Nama) dan Data (Angka) untuk Chart.js
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
