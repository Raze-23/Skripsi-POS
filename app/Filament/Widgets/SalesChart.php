<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Grafik Penjualan Bulanan';

    protected static string $color = 'info';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $year = $this->filters['year'] ?? date('Y');

        $data = collect(range(1, 12))->map(function ($month) use ($year) {
            return Transaction::where('status', 'Selesai')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total_harga');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan ' . $year,
                    'data' => $data->toArray(),
                    'fill' => 'start',
                    'tension' => 0.4,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
