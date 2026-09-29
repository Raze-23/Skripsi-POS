<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\SawPriorityMovementTracker;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SawPriorityWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.saw-priority-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public const BOBOT = [
        'penjualan' => 0.35, // C1 – Benefit
        'kedaluwarsa' => 0.30, // C2 – Cost
        'stok' => 0.25, // C3 – Cost
        'req_mitra' => 0.05, // C4 – Benefit
        'req_owner' => 0.05, // C5 – Benefit
    ];

    private const NO_BATCH_EXPIRY_DAYS = 999;

    private const MIN_VALID_YEAR = 2000;

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'owner']);
    }

    public function getWidgetData(): array
    {
        $year = $this->resolveYear();

        $products = Product::all();

        if ($products->isEmpty()) {
            return [
                'darurat' => [],
                'ranked' => [],
                'aman_count' => 0,
            ];
        }

        // ── C1: Volume Penjualan (Benefit) — difilter sesuai $year ─────────────
        // Source A: TransactionDetail → parent Transaction status = 'Selesai'
        $kasirSales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->whereYear('transactions.created_at', $year)
            ->where('transactions.status', 'Selesai')
            ->select(
                'product_batches.product_id',
                DB::raw('SUM(transaction_details.qty) as total_qty')
            )
            ->groupBy('product_batches.product_id')
            ->pluck('total_qty', 'product_id');

        // Source B: ConsignmentReturn column terjual, status = 'selesai'
        $consignmentSales = DB::table('consignment_returns')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_returns.product_batch_id')
            ->whereYear(DB::raw('COALESCE(consignment_returns.divalidasi_pada, consignment_returns.created_at)'), $year)
            ->where('consignment_returns.status', 'selesai')
            ->select(
                'product_batches.product_id',
                DB::raw('SUM(consignment_returns.terjual) as total_qty')
            )
            ->groupBy('product_batches.product_id')
            ->pluck('total_qty', 'product_id');

        // ── C2: Nearest Expiry Date per Product (Cost) — SELALU real-time ──────
        // Catatan: product_batches tidak menggunakan soft delete.
        $nearestExpiry = DB::table('product_batches')
            ->leftJoin('consignment_stocks', 'product_batches.id', '=', 'consignment_stocks.product_batch_id')
            ->where(function ($query) {
                $query->where('product_batches.stok_toko', '>', 0)
                    ->orWhere('consignment_stocks.stok_titipan', '>', 0);
            })
            ->select(
                'product_batches.product_id',
                DB::raw('MIN(product_batches.tanggal_kedaluwarsa) as min_expiry')
            )
            ->groupBy('product_batches.product_id')
            ->pluck('min_expiry', 'product_id');

        // ── C3: Sisa Stok Total (Cost) — SELALU real-time ───────────────────────
        // Catatan: product_batches tidak menggunakan soft delete.
        $totalStok = DB::table('product_batches')
            ->leftJoin('consignment_stocks', 'product_batches.id', '=', 'consignment_stocks.product_batch_id')
            ->select(
                'product_batches.product_id',
                DB::raw('SUM(COALESCE(product_batches.stok_toko, 0) + COALESCE(consignment_stocks.stok_titipan, 0)) as total_stok')
            )
            ->groupBy('product_batches.product_id')
            ->pluck('total_stok', 'product_id');

        // ── C4: Request Mitra / Apotek (Benefit) — SELALU real-time ────────────
        $reqMitra = DB::table('product_requests')
            ->where('tipe_request', 'restok_apotek')
            ->where('status', 'diproses')
            ->select('product_id', DB::raw('SUM(jumlah) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        // ── C5: Request Owner (Benefit) — SELALU real-time ─────────────────────
        $reqOwner = DB::table('product_requests')
            ->where('tipe_request', 'produksi_owner')
            ->where('status', 'diproses')
            ->select('product_id', DB::raw('SUM(jumlah) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        // ── Step 1: Build raw data matrix ───────────────────────────────────────
        $rawData = [];
        $daruratData = [];
        $amanCount = 0;
        $hariIni = now()->startOfDay();

        foreach ($products as $product) {
            $id = $product->id;
            $stok = (int) ($totalStok[$id] ?? 0);

            // Klasifikasi berdasarkan batas aman
            if ($stok === 0) {
                // Darurat: Wajib restok (Bypass SAW)
                $daruratData[] = [
                    'id' => $id,
                    'nama' => $product->nama,
                    'stok_raw' => 0,
                ];
                continue;
            } elseif ($stok > 10) {
                // Aman: Tidak perlu dianalisis
                $amanCount++;
                continue;
            }

            // Stok 1 - 10: Masuk analisis SAW
            $c1 = (float) (($kasirSales[$id] ?? 0) + ($consignmentSales[$id] ?? 0));

            if (isset($nearestExpiry[$id])) {
                $diffDays = $hariIni->copy()->diffInDays(
                    Carbon::parse($nearestExpiry[$id])->startOfDay(),
                    false
                );
                $c2 = max(1, $diffDays < 0 ? 0 : $diffDays);
            } else {
                $c2 = self::NO_BATCH_EXPIRY_DAYS;
            }

            $c3 = max(1, (float) $stok);
            $c4 = (float) ($reqMitra[$id] ?? 0);
            $c5 = (float) ($reqOwner[$id] ?? 0);

            $rawData[] = [
                'id' => $id,
                'nama' => $product->nama,
                'c1' => $c1,
                'c2' => $c2,
                'c3' => $c3,
                'c4' => $c4,
                'c5' => $c5,
                'expiry_raw' => $nearestExpiry[$id] ?? null,
                'stok_raw' => (float) $stok,
            ];
        }

        if (empty($rawData)) {
            return [
                'darurat' => $daruratData,
                'ranked' => [],
                'aman_count' => $amanCount,
            ];
        }

        // ── Step 2: Find extremes ────────────────────────────────────────────────
        $maxC1 = max(array_column($rawData, 'c1'));
        $minC2 = min(array_column($rawData, 'c2'));
        $minC3 = min(array_column($rawData, 'c3'));
        $maxC4 = max(array_column($rawData, 'c4'));
        $maxC5 = max(array_column($rawData, 'c5'));

        $adaDataPenjualanTahunIni = $maxC1 > 0;

        // Fallback ke 1 untuk mencegah DivisionByZeroError
        $maxC1 = $maxC1 ?: 1;
        $minC2 = $minC2 ?: 1;
        $minC3 = $minC3 ?: 1;
        $maxC4 = $maxC4 ?: 1;
        $maxC5 = $maxC5 ?: 1;

        // ── Step 3 & 4: Normalize + Score ───────────────────────────────────────
        $rankedData = [];

        foreach ($rawData as $row) {
            $normC1 = $row['c1'] / $maxC1;  // Benefit: raw / max
            $normC2 = $minC2 / $row['c2'];  // Cost:    min / raw
            $normC3 = $minC3 / $row['c3'];  // Cost:    min / raw
            $normC4 = $row['c4'] / $maxC4;  // Benefit: raw / max
            $normC5 = $row['c5'] / $maxC5;  // Benefit: raw / max

            $kontribusi = [
                'penjualan' => self::BOBOT['penjualan'] * $normC1,
                'kedaluwarsa' => self::BOBOT['kedaluwarsa'] * $normC2,
                'stok' => self::BOBOT['stok'] * $normC3,
                'req_mitra' => self::BOBOT['req_mitra'] * $normC4,
                'req_owner' => self::BOBOT['req_owner'] * $normC5,
            ];

            $score = array_sum($kontribusi);

            $row['score'] = round($score, 4);
            $row['skor_persen'] = (int) round($score * 100);

            arsort($kontribusi);
            $row['faktor_utama'] = array_key_first($kontribusi);

            $row['c1_display'] = (int) $row['c1'];
            $row['c2_display'] = $row['expiry_raw']
                ? $hariIni->copy()->diffInDays(Carbon::parse($row['expiry_raw'])->startOfDay(), false)
                : null;
            $row['c3_display'] = (int) $row['stok_raw'];
            $row['c4_display'] = (int) $row['c4'];
            $row['c5_display'] = (int) $row['c5'];

            $row['tahun_dianalisis'] = $year;
            $row['ada_data_penjualan_tahun_ini'] = $adaDataPenjualanTahunIni;

            $rankedData[] = $row;
        }

        // ── Step 5: Sort descending by final score ───────────────────────────────
        usort($rankedData, fn ($a, $b) => $b['score'] <=> $a['score']);

        $rankedData = app(SawPriorityMovementTracker::class)->track($rankedData, $year);

        return [
            'darurat' => $daruratData,
            'ranked' => array_slice($rankedData, 0, 6),
            'aman_count' => $amanCount,
        ];
    }

    private function resolveYear(): int
    {
        $currentYear = (int) now()->year;
        $raw = $this->filters['year'] ?? $currentYear;

        if (! is_numeric($raw)) {
            return $currentYear;
        }

        $year = (int) $raw;

        if ($year < self::MIN_VALID_YEAR || $year > $currentYear + 1) {
            return $currentYear;
        }

        return $year;
    }
}
