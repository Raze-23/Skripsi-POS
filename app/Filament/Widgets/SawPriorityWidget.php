<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class SawPriorityWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.saw-priority-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 4;
    
    public function getRankedProducts(): array
    {
        $year = (int) ($this->filters['year'] ?? now()->year);
        $products = Product::all();

        if ($products->isEmpty()) return [];

        $kasirSales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->whereYear('transactions.created_at', $year)
            ->where('transactions.status', 'Selesai')
            ->select('product_batches.product_id', DB::raw('SUM(transaction_details.qty) as total_qty'))
            ->groupBy('product_batches.product_id')
            ->pluck('total_qty', 'product_id');

        $apotekSales = DB::table('consignment_returns')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_returns.product_batch_id')
            ->whereYear('consignment_returns.created_at', $year)
            ->select('product_batches.product_id', DB::raw('SUM(consignment_returns.terjual) as total_qty'))
            ->groupBy('product_batches.product_id')
            ->pluck('total_qty', 'product_id');

        $nearestExpiry = ProductBatch::where('stok_toko', '>', 0)
            ->select('product_id', DB::raw('MIN(tanggal_kedaluwarsa) as min_expiry'))
            ->groupBy('product_id')
            ->pluck('min_expiry', 'product_id');

        $totalStock = ProductBatch::where('stok_toko', '>', 0)
            ->select('product_id', DB::raw('SUM(stok_toko) as total_stok'))
            ->groupBy('product_id')
            ->pluck('total_stok', 'product_id');

        $rawData = [];
        $hariIni = now()->startOfDay();

        foreach ($products as $p) {
            $terjualKasir = $kasirSales[$p->id] ?? 0;
            $terjualApotek = $apotekSales[$p->id] ?? 0;
            
            $totalTerjual = $terjualKasir + $terjualApotek;

            $hasActiveBatch = isset($nearestExpiry[$p->id]);
            $expiry = $nearestExpiry[$p->id] ?? now()->toDateString();
            $sisaHari = $hasActiveBatch 
                ? $hariIni->copy()->diffInDays(Carbon::parse($expiry)->startOfDay(), false) 
                : 0;

            $rawData[] = [
                'id' => $p->id,
                'nama' => $p->nama,
                'c1' => $totalTerjual,
                'c2' => max(1, $p->estimasi_masak), 
                'c3' => max(1, $sisaHari), 
                'c4' => max(1, $totalStock[$p->id] ?? 0), 
            ];
        }

        if (empty($rawData)) return [];

        $maxC1 = max(array_column($rawData, 'c1')) ?: 1;
        $maxC2 = max(array_column($rawData, 'c2')) ?: 1;
        $minC3 = min(array_column($rawData, 'c3')) ?: 1;
        $minC4 = min(array_column($rawData, 'c4')) ?: 1;

        $rankedData = [];

        foreach ($rawData as $row) {
            $normC1 = $row['c1'] / $maxC1; 
            $normC2 = $row['c2'] / $maxC2; 
            $normC3 = $minC3 / $row['c3']; 
            $normC4 = $minC4 / $row['c4']; 

            $score = (0.45 * $normC1) + (0.25 * $normC2) + (0.20 * $normC3) + (0.10 * $normC4);

            $row['score'] = round($score, 4);

            $expiryDisplay = $nearestExpiry[$row['id']] ?? null;
            $row['c3_display'] = $expiryDisplay
                ? $hariIni->copy()->diffInDays(Carbon::parse($expiryDisplay)->startOfDay(), false)
                : null;
            $row['c4_display'] = $totalStock[$row['id']] ?? 0;

            $rankedData[] = $row;
        }

        usort($rankedData, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($rankedData, 0, 6);
    }
}