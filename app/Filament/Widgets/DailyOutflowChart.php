<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class DailyOutflowChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Volume Penjualan';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    public ?string $filter = null;

    public function mount(): void
    {
        $this->filter = (string) now()->month;
    }

    protected function getFilters(): ?array
    {
        return [
            '1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Maret',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'Agustus',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];
    }

    protected function getData(): array
    {
        $year = (int) ($this->filters['year'] ?? now()->year);
        $month = (int) ($this->filter ?? now()->month);
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $kasirDaily = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereYear('transactions.created_at', $year)
            ->whereMonth('transactions.created_at', $month)
            ->where('transactions.status', 'Selesai')
            ->select(
                DB::raw('DAY(transactions.created_at) as hari'),
                DB::raw('SUM(transaction_details.qty) as total_qty')
            )
            ->groupBy('hari')
            ->pluck('total_qty', 'hari');

        $apotekDaily = DB::table('consignment_returns')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->select(
                DB::raw('DAY(created_at) as hari'),
                DB::raw('SUM(terjual) as total_qty')
            )
            ->groupBy('hari')
            ->pluck('total_qty', 'hari');

        $labels = [];
        $dataKasir = [];
        $dataApotek = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $labels[] = $d . ' ' . Carbon::createFromDate($year, $month, $d)->translatedFormat('M');
            $dataKasir[] = (int) ($kasirDaily[$d] ?? 0);
            $dataApotek[] = (int) ($apotekDaily[$d] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Terjual di Kasir (Pcs)',
                    'data' => $dataKasir,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Laku di Mitra Apotek (Pcs)',
                    'data' => $dataApotek,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderWidth' => 2,
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
