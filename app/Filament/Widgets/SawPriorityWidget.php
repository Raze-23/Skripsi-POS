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
        'penjualan'   => 0.45,
        'kedaluwarsa' => 0.40,
        'req_mitra'   => 0.10,
        'req_owner'   => 0.05,
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
            ->where('consignment_returns.status', 'selesai')
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

        $reqMitra = DB::table('product_requests')
            ->where('tipe_request', 'restok_apotek')
            ->where('status', 'pending')
            ->select('product_id', DB::raw('SUM(jumlah) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $reqOwner = DB::table('product_requests')
            ->where('tipe_request', 'produksi_owner')
            ->where('status', 'pending')
            ->select('product_id', DB::raw('SUM(jumlah) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $rawData = [];
        $hariIni = now()->startOfDay();

        foreach ($products as $p) {
            $c1 = ($kasirSales[$p->id] ?? 0) + ($apotekSales[$p->id] ?? 0);

            $hasActiveBatch = isset($nearestExpiry[$p->id]);
            $expiry = $nearestExpiry[$p->id] ?? now()->toDateString();
            $sisaHari = $hasActiveBatch
                ? $hariIni->copy()->diffInDays(Carbon::parse($expiry)->startOfDay(), false)
                : 999;
            $c2 = max(1, $sisaHari < 0 ? 0 : $sisaHari);

            $c3 = $reqMitra[$p->id] ?? 0;
            $c4 = $reqOwner[$p->id] ?? 0;

            $rawData[] = [
                'id'   => $p->id,
                'nama' => $p->nama,
                'c1'   => $c1,
                'c2'   => $c2,
                'c3'   => $c3,
                'c4'   => $c4,
            ];
        }

        if (empty($rawData)) return [];

        $maxC1 = max(array_column($rawData, 'c1')) ?: 1;
        $minC2 = min(array_column($rawData, 'c2')) ?: 1;
        $maxC3 = max(array_column($rawData, 'c3')) ?: 1;
        $maxC4 = max(array_column($rawData, 'c4')) ?: 1;

        $rankedData = [];

        foreach ($rawData as $row) {
            $normC1 = $row['c1'] / $maxC1;
            $normC2 = $minC2 / $row['c2'];
            $normC3 = $row['c3'] / $maxC3;
            $normC4 = $row['c4'] / $maxC4;

            $kontribusi = [
                'penjualan'   => self::BOBOT['penjualan'] * $normC1,
                'kedaluwarsa' => self::BOBOT['kedaluwarsa'] * $normC2,
                'req_mitra'   => self::BOBOT['req_mitra'] * $normC3,
                'req_owner'   => self::BOBOT['req_owner'] * $normC4,
            ];

            $score = array_sum($kontribusi);

            $row['score'] = round($score, 4);
            $row['skor_persen'] = (int) round($score * 100);

            arsort($kontribusi);
            $row['faktor_utama'] = array_key_first($kontribusi);

            $expiryDisplay = $nearestExpiry[$row['id']] ?? null;
            $row['c2_display'] = $expiryDisplay
                ? $hariIni->copy()->diffInDays(Carbon::parse($expiryDisplay)->startOfDay(), false)
                : null;
            $row['c1_display'] = $row['c1'];
            $row['c3_display'] = $row['c3'];
            $row['c4_display'] = $row['c4'];

            $rankedData[] = $row;
        }

        usort($rankedData, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($rankedData, 0, 6);
    }
}