<?php

namespace App\Console\Commands;

use App\Models\ConsignmentReturn;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CekProduk extends Command
{
    protected $signature = 'app:cek-produk';

    public function handle()
    {
        $admins = User::where('role', 'admin')->get();
        if ($admins->isEmpty()) {
            $this->error('Admin belum ada di database.');
            return;
        }

        // Kumpulkan semua user (admin + mitra aktif) untuk dedup alert_key
        $semuaUser = User::whereIn('role', ['admin', 'mitra'])
            ->where('status', 'aktif')
            ->get();

        $alertKeyPerUser = [];
        foreach ($semuaUser as $user) {
            $alertKeyPerUser[$user->id] = $user->notifications()
                ->pluck('data')
                ->map(fn ($data) => data_get($data, 'viewData.alert_key'))
                ->filter()
                ->flip()
                ->toArray();
        }

        $terkirim = 0;
        $keyUntukDihapus = [];

        // Helper: kirim notifikasi ke target tertentu jika belum pernah dikirim
        $kirimKeUser = function (string $key, Notification $notification, $targets) use ($alertKeyPerUser, &$terkirim) {
            $tujuan = $targets->filter(fn ($user) => ! isset($alertKeyPerUser[$user->id][$key]));

            if ($tujuan->isEmpty()) {
                return;
            }

            $notification->viewData(['alert_key' => $key])->sendToDatabase($tujuan);
            $terkirim += $tujuan->count();
        };

        $hariIni = Carbon::now()->startOfDay();

        // =============================================
        // BAGIAN 1: CEK KEDALUWARSA STOK TOKO (≤ 7 hari)
        // =============================================
        $batasToko = $hariIni->copy()->addDays(7);
        $batchKritis = ProductBatch::where('stok_toko', '>', 0)
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', $batasToko)
            ->with('product')
            ->get();

        foreach ($batchKritis as $batch) {
            $sisaHari = (int) $hariIni->copy()->diffInDays(Carbon::parse($batch->tanggal_kedaluwarsa)->startOfDay(), false);
            $sudahExpired = $sisaHari < 0;

            $keyWarning = "toko_warning_batch_{$batch->id}";
            $keyExpired = "toko_expired_batch_{$batch->id}";

            if ($sudahExpired) {
                $keyUntukDihapus[$keyWarning] = true;

                $kirimKeUser($keyExpired, Notification::make()
                    ->danger()
                    ->icon('heroicon-o-x-circle')
                    ->title("🚨 DARURAT: {$batch->product->nama} SUDAH KEDALUWARSA!")
                    ->body("Batch {$batch->batch_code} sudah kedaluwarsa " . abs($sisaHari) . " hari lalu! Masih tersisa {$batch->stok_toko} pcs di toko. SEGERA BUANG/TARIK BARANG INI! (Kedaluwarsa: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . ")"),
                    $admins
                );
            } else {
                $kirimKeUser($keyWarning, Notification::make()
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->title("Peringatan: {$batch->product->nama} (Batch: {$batch->batch_code})")
                    ->body("Terdapat {$batch->stok_toko} pcs di stok toko yang mendekati kedaluwarsa (Sisa {$sisaHari} hari). Segera periksa barang!"),
                    $admins
                );
            }
        }

        // =============================================
        // BAGIAN 2: CEK KEDALUWARSA STOK MITRA (≤ 30 hari)
        // =============================================
        $batasMitra = $hariIni->copy()->addDays(30);
        $batchMitraKritis = ProductBatch::whereHas('consignmentStocks', function ($query) {
                $query->where('stok_titipan', '>', 0);
            })
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', $batasMitra)
            ->with(['product', 'consignmentStocks.partner'])
            ->get();

        foreach ($batchMitraKritis as $batch) {
            $sisaHari = (int) $hariIni->copy()->diffInDays(Carbon::parse($batch->tanggal_kedaluwarsa)->startOfDay(), false);
            $sudahExpired = $sisaHari < 0;

            foreach ($batch->consignmentStocks as $titipan) {
                if ($titipan->stok_titipan <= 0) continue;

                $keyWarning = "mitra_warning_batch_{$batch->id}_partner_{$titipan->partner_id}";
                $keyExpired = "mitra_expired_batch_{$batch->id}_partner_{$titipan->partner_id}";

                if ($sudahExpired) {
                    $keyUntukDihapus[$keyWarning] = true;

                    $kirimKeUser($keyExpired, Notification::make()
                        ->danger()
                        ->icon('heroicon-o-x-circle')
                        ->title("🚨 DARURAT: Tarik {$batch->product->nama} dari {$titipan->partner->nama_apotek}!")
                        ->body("Batch {$batch->batch_code} sudah kedaluwarsa " . abs($sisaHari) . " hari lalu! Masih ada {$titipan->stok_titipan} pcs di apotek. SEGERA TARIK BARANG! (Kedaluwarsa: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . ")"),
                        $admins
                    );
                } else {
                    $kirimKeUser($keyWarning, Notification::make()
                        ->warning()
                        ->icon('heroicon-o-truck')
                        ->title("Tarik Barang dari {$titipan->partner->nama_apotek}")
                        ->body("Produk {$batch->product->nama} (Batch: {$batch->batch_code}) sebanyak {$titipan->stok_titipan} pcs mendekati kedaluwarsa (Sisa {$sisaHari} hari, Tgl: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . "). Segera lakukan penarikan!"),
                        $admins
                    );
                }
            }
        }

        // =============================================
        // BAGIAN 3: PERINGATAN STOK MENIPIS (≤ 3 pcs)
        // =============================================
        $semuaProduk = Product::withSum(
                ['productBatches' => fn ($q) => $q->where('stok_toko', '>', 0)],
                'stok_toko'
            )
            ->get();

        foreach ($semuaProduk as $produk) {
            $totalStok = (int) ($produk->product_batches_sum_stok_toko ?? 0);
            $key = "stok_menipis_product_{$produk->id}";

            if ($totalStok > 0 && $totalStok <= 3) {
                $kirimKeUser($key, Notification::make()
                    ->info()
                    ->icon('heroicon-o-inbox-stack')
                    ->title("Stok Menipis: {$produk->nama}")
                    ->body("Perhatian! Stok gudang untuk {$produk->nama} saat ini hanya tersisa {$totalStok} pcs. Mohon segera jadwalkan produksi atau restok untuk menghindari kekosongan barang."),
                    $admins
                );
            } else {
                // Stok sudah aman (> 3 atau habis), hapus notif lama jika ada
                $keyUntukDihapus[$key] = true;
            }
        }


        // =============================================
        // CLEANUP: Hapus notifikasi yang sudah tidak relevan
        // =============================================
        if (! empty($keyUntukDihapus)) {
            $keys = array_keys($keyUntukDihapus);

            foreach ($semuaUser as $user) {
                $user->notifications()
                    ->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhere('data->viewData->alert_key', $key);
                        }
                    })
                    ->delete();
            }
        }

        $this->info("Pemeriksaan selesai dilakukan. {$terkirim} notifikasi baru dikirim.");
    }
}