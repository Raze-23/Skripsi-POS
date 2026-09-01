<?php

namespace App\Console\Commands;

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

        $alertKeyPerAdmin = [];
        foreach ($admins as $admin) {
            $alertKeyPerAdmin[$admin->id] = $admin->notifications()
                ->pluck('data')
                ->map(fn ($data) => data_get($data, 'viewData.alert_key'))
                ->filter()
                ->flip()
                ->toArray();
        }

        $terkirim = 0;
        $keyUntukDihapus = [];

        $kirimJikaBelumAda = function (string $key, Notification $notification) use ($admins, $alertKeyPerAdmin, &$terkirim) {
            $tujuan = $admins->filter(fn ($admin) => ! isset($alertKeyPerAdmin[$admin->id][$key]));

            if ($tujuan->isEmpty()) {
                return;
            }

            $notification->viewData(['alert_key' => $key])->sendToDatabase($tujuan);
            $terkirim += $tujuan->count();
        };

        $hariIni = Carbon::now()->startOfDay();

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

                $kirimJikaBelumAda($keyExpired, Notification::make()
                    ->danger()
                    ->icon('heroicon-o-x-circle')
                    ->title("🚨 DARURAT: {$batch->product->nama} SUDAH KEDALUWARSA!")
                    ->body("Batch {$batch->batch_code} sudah kedaluwarsa " . abs($sisaHari) . " hari lalu! Masih tersisa {$batch->stok_toko} pcs di toko. SEGERA BUANG/TARIK BARANG INI! (Kedaluwarsa: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . ")")
                );
            } else {
                $kirimJikaBelumAda($keyWarning, Notification::make()
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->title("Peringatan: {$batch->product->nama} (Batch: {$batch->batch_code})")
                    ->body("Terdapat {$batch->stok_toko} pcs di stok toko yang mendekati kedaluwarsa (Sisa {$sisaHari} hari). Segera periksa barang!")
                );
            }
        }

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

                    $kirimJikaBelumAda($keyExpired, Notification::make()
                        ->danger()
                        ->icon('heroicon-o-x-circle')
                        ->title("🚨 DARURAT: Tarik {$batch->product->nama} dari {$titipan->partner->nama_apotek}!")
                        ->body("Batch {$batch->batch_code} sudah kedaluwarsa " . abs($sisaHari) . " hari lalu! Masih ada {$titipan->stok_titipan} pcs di apotek. SEGERA TARIK BARANG! (Kedaluwarsa: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . ")")
                    );
                } else {
                    $kirimJikaBelumAda($keyWarning, Notification::make()
                        ->warning()
                        ->icon('heroicon-o-truck')
                        ->title("Tarik Barang dari {$titipan->partner->nama_apotek}")
                        ->body("Produk {$batch->product->nama} (Batch: {$batch->batch_code}) sebanyak {$titipan->stok_titipan} pcs mendekati kedaluwarsa (Sisa {$sisaHari} hari, Tgl: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . "). Segera lakukan penarikan!")
                    );
                }
            }
        }

        if (! empty($keyUntukDihapus)) {
            $keys = array_keys($keyUntukDihapus);

            foreach ($admins as $admin) {
                $admin->notifications()
                    ->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhere('data->viewData->alert_key', $key);
                        }
                    })
                    ->delete();
            }
        }

        $this->info("Pemeriksaan kedaluwarsa selesai dilakukan. {$terkirim} notifikasi baru dikirim.");
    }
}