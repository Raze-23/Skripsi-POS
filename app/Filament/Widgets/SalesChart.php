<?php

namespace App\Filament\Widgets;

use App\Models\ConsignmentReturn;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pendapatan Harian';

    protected static string $color = 'info';

    protected static ?int $sort = 2;

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

        $totalDays = (int) $startDate->copy()->diffInDays($endDate->copy()->startOfDay()) + 1;

        $labels = [];
        $days = [];
        for ($i = 0; $i < $totalDays; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->translatedFormat('d M');
            $days[] = $date->format('Y-m-d');
        }

        $kasirPerDay = DB::table('transactions')
            ->where('status', 'Selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_harga) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $apotekPerDay = DB::table('consignment_returns')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_returns.product_batch_id')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereBetween('consignment_returns.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(consignment_returns.created_at) as date'),
                DB::raw('SUM(consignment_returns.terjual * products.harga_jual) as total')
            )
            ->groupBy('date')
            ->pluck('total', 'date');

        $data = [];
        foreach ($days as $day) {
            $data[] = ($kasirPerDay[$day] ?? 0) + ($apotekPerDay[$day] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pendapatan',
                    'data' => $data,
                    'fill' => 'start',
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
