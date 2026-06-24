<?php

namespace App\Filament\Widgets;

use App\Models\ConsignmentReturn; // TAMBAHAN: Import model riwayat penarikan
use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pendapatan Perbulan';

    protected static string $color = 'info';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $year = $this->filters['year'] ?? date('Y');

        $data = collect(range(1, 12))->map(function ($month) use ($year) {

            // 1. Hitung Omzet dari Mesin Kasir (Transaksi Langsung)
            $pemasukanKasir = Transaction::where('status', 'Selesai')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total_harga');

            // 2. Hitung Omzet dari Apotek (Konsinyasi yang laku)
            $pemasukanApotek = ConsignmentReturn::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->with('product')
                ->get()
                ->sum(fn ($return) => $return->terjual * ($return->product->harga_jual ?? 0));

            // 3. Gabungkan keduanya sebagai total pendapatan di bulan tersebut
            return $pemasukanKasir + $pemasukanApotek;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Total Pendapatan ' . $year,
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
