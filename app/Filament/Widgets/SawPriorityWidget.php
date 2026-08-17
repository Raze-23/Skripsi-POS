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

    public const BOBOT = [
        'penjualan'   => 0.40,
        'produksi'    => 0.10,
        'kedaluwarsa' => 0.20,
        'stok'        => 0.30,
    ];

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

        $nearestExpiry = DB::table('product_batches')
            ->leftJoin('consignment_stocks', 'product_batches.id', '=', 'consignment_stocks.product_batch_id')
            ->where(function ($query) {
                $query->where('product_batches.stok_toko', '>', 0)
                      ->orWhere('consignment_stocks.stok_titipan', '>', 0);
            })
            ->select('product_batches.product_id', DB::raw('MIN(product_batches.tanggal_kedaluwarsa) as min_expiry'))
            ->groupBy('product_batches.product_id')
            ->pluck('min_expiry', 'product_id');

        $tokoStock = ProductBatch::select('product_id', DB::raw('SUM(stok_toko) as total_stok'))
            ->groupBy('product_id')
            ->pluck('total_stok', 'product_id');

        $apotekStock = DB::table('consignment_stocks')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_stocks.product_batch_id')
            ->select('product_batches.product_id', DB::raw('SUM(consignment_stocks.stok_titipan) as total_stok'))
            ->groupBy('product_batches.product_id')
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
                : 999;
            $sisaHari = $sisaHari < 0 ? 0 : $sisaHari;

            $totalSeluruhStok = ($tokoStock[$p->id] ?? 0) + ($apotekStock[$p->id] ?? 0);

            $rawData[] = [
                'id' => $p->id,
                'nama' => $p->nama,
                'c1' => $totalTerjual,
                'c2' => max(1, $p->estimasi_masak),
                'c2_raw' => $p->estimasi_masak,
                'c3' => max(1, $sisaHari),
                'c4' => max(1, $totalSeluruhStok),
            ];
        }

        if (empty($rawData)) return [];

        $maxC1 = max(array_column($rawData, 'c1')) ?: 1;
        $minC2 = min(array_column($rawData, 'c2')) ?: 1;
        $minC3 = min(array_column($rawData, 'c3')) ?: 1;
        $minC4 = min(array_column($rawData, 'c4')) ?: 1;

        $rankedData = [];

        foreach ($rawData as $row) {
            $normC1 = $row['c1'] / $maxC1;
            $normC2 = $minC2 / $row['c2'];
            $normC3 = $minC3 / $row['c3'];
            $normC4 = $minC4 / $row['c4'];

            $kontribusi = [
                'penjualan'   => self::BOBOT['penjualan'] * $normC1,
                'produksi'    => self::BOBOT['produksi'] * $normC2,
                'kedaluwarsa' => self::BOBOT['kedaluwarsa'] * $normC3,
                'stok'        => self::BOBOT['stok'] * $normC4,
            ];

            $score = array_sum($kontribusi);

            $row['score'] = round($score, 4);
            $row['skor_persen'] = (int) round($score * 100);

            arsort($kontribusi);
            $row['faktor_utama'] = array_key_first($kontribusi);

            $expiryDisplay = $nearestExpiry[$row['id']] ?? null;
            $row['c3_display'] = $expiryDisplay
                ? $hariIni->copy()->diffInDays(Carbon::parse($expiryDisplay)->startOfDay(), false)
                : null;
            $row['c4_display'] = ($tokoStock[$row['id']] ?? 0) + ($apotekStock[$row['id']] ?? 0);
            $row['c1_display'] = $row['c1'];
            $row['c2_display'] = $row['c2_raw'];

            $rankedData[] = $row;
        }

        usort($rankedData, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($rankedData, 0, 6);
    }
}