<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class SawPriorityWidget extends Widget
{
    protected static string $view = 'filament.widgets.saw-priority-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 3;

    public function getRankedProductsProperty()
    {
        $products = Product::all();

        // 1. Data Penjualan dari Kasir (30 hari terakhir)
        $kasirSales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.created_at', '>=', now()->subDays(30))
            ->where('transactions.status', 'Selesai')
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        // 2. Data Penjualan dari Apotek / Konsinyasi (30 hari terakhir)
        $apotekSales = DB::table('consignment_returns')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('product_id', DB::raw('SUM(terjual) as total_qty'))
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        $rawData = [];

        foreach ($products as $p) {
            // Hitung sisa hari kedaluwarsa
            $sisaHari = now()->startOfDay()->diffInDays(Carbon::parse($p->tanggal_kedaluwarsa)->startOfDay(), false);

            // ==========================================
            // GABUNGKAN PENJUALAN KASIR & APOTEK
            // ==========================================
            $terjualKasir = $kasirSales[$p->id] ?? 0;
            $terjualApotek = $apotekSales[$p->id] ?? 0;
            $totalTerjual = $terjualKasir + $terjualApotek;

            $rawData[] = [
                'id' => $p->id,
                'nama' => $p->nama,
                'c1' => $totalTerjual,                  // Terjual (C1) = Gabungan Kasir + Apotek
                'c2' => max(1, $p->estimasi_masak),     // Masak (C2) - max(1) agar tidak dibagi 0
                'c3' => max(1, $sisaHari),              // Kedaluwarsa (C3)
                'c4' => max(1, $p->stok_toko),          // Stok (C4) - Menggunakan stok toko saja sesuai instruksi
            ];
        }

        // Cari Nilai Max (untuk Benefit) dan Min (untuk Cost)
        $maxC1 = max(array_column($rawData, 'c1')) ?: 1;
        $minC2 = min(array_column($rawData, 'c2')) ?: 1;
        $minC3 = min(array_column($rawData, 'c3')) ?: 1;
        $minC4 = max(1, min(array_column($rawData, 'c4'))); // Pastikan minimal 1 agar tidak error dibagi 0

        $rankedData = [];

        // Hitung Normalisasi & Skor Akhir SAW
        foreach ($rawData as $row) {
            $normC1 = $row['c1'] / $maxC1;       // C1 = Benefit (Nilai / Max)
            $normC2 = $minC2 / $row['c2'];       // C2 = Cost (Min / Nilai)
            $normC3 = $minC3 / $row['c3'];       // C3 = Cost (Min / Nilai)
            $normC4 = $minC4 / $row['c4'];       // C4 = Cost (Min / Nilai)

            // Kali Bobot: C1(45%), C2(25%), C3(20%), C4(10%)
            $score = (0.45 * $normC1) + (0.25 * $normC2) + (0.20 * $normC3) + (0.10 * $normC4);

            $row['score'] = round($score, 4);

            // Data asli untuk ditampilkan di layar UI
            $productAsli = Product::find($row['id']);
            $row['c3_display'] = now()->startOfDay()->diffInDays(Carbon::parse($productAsli->tanggal_kedaluwarsa)->startOfDay(), false);
            $row['c4_display'] = $productAsli->stok_toko;

            $rankedData[] = $row;
        }

        // Urutkan dari Skor Tertinggi ke Terendah (Ranking 1 di atas)
        usort($rankedData, fn($a, $b) => $b['score'] <=> $a['score']);

        // Ambil 5 produk prioritas teratas
        return array_slice($rankedData, 0, 5);
    }
}
