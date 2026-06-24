<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DailySalesChart extends ChartWidget
{
    protected static ?string $heading = 'Total Penjualan Perhari';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // TAHAP 1: Ambil SEMUA produk yang memiliki rekam jejak penjualan di 7 hari terakhir (Tanpa Limit)
        $soldProducts = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'Selesai')
            ->select('products.id', 'products.nama')
            ->groupBy('products.id', 'products.nama')
            ->get();

        // Jika tidak ada penjualan sama sekali, kembalikan grafik kosong agar tidak error
        if ($soldProducts->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }

        $soldProductIds = $soldProducts->pluck('id')->toArray();

        // TAHAP 2: Ambil rincian qty per hari untuk produk-produk yang terjual saja
        $dailySales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereIn('transaction_details.product_id', $soldProductIds)
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'Selesai')
            ->select(
                'transaction_details.product_id',
                DB::raw('DATE(transactions.created_at) as date'),
                DB::raw('SUM(transaction_details.qty) as daily_qty')
            )
            ->groupBy('transaction_details.product_id', 'date')
            ->get();

        // TAHAP 3: Siapkan Sumbu X (7 Hari Terakhir)
        $labels = [];
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays(6 - $i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->translatedFormat('d M');
            $days[] = $date;
        }

        // TAHAP 4: Susun Dataset Sumbu Y (Volume per Produk)
        $datasets = [];

        foreach ($soldProducts as $index => $product) {
            $data = [];
            foreach ($days as $day) {
                // Cocokkan data penjualan
                $sale = $dailySales->where('product_id', $product->id)->where('date', $day)->first();
                $data[] = $sale ? (int) $sale->daily_qty : 0;
            }

            // Algoritma pembuat warna dinamis (Golden Ratio) agar warna tidak akan pernah habis
            $hue = ($index * 137.508) % 360;
            $dynamicColor = "hsl({$hue}, 70%, 50%)";

            $datasets[] = [
                'label' => $product->nama,
                'data' => $data,
                'borderColor' => $dynamicColor,
                'backgroundColor' => $dynamicColor,
                'borderWidth' => 2, // Garis dipertipis sedikit agar tidak terlalu menumpuk jika banyak produk
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
