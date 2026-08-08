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
        $admin = User::first();
        if (!$admin) {
            $this->error('Admin belum ada di database.');
            return;
        }
        
        $hariIni = Carbon::now()->startOfDay();
        $batasToko = $hariIni->copy()->addDays(7);
        $batchKritis = ProductBatch::where('stok_toko', '>', 0)
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', $batasToko)
            ->with('product')
            ->get();

        foreach ($batchKritis as $batch) {
            $sisaHari = (int) $hariIni->copy()->diffInDays(Carbon::parse($batch->tanggal_kedaluwarsa)->startOfDay(), false);

            if ($sisaHari < 0) {
                Notification::make()
                    ->danger()
                    ->icon('heroicon-o-x-circle')
                    ->title("🚨 DARURAT: {$batch->product->nama} SUDAH KEDALUWARSA!")
                    ->body("Batch {$batch->batch_code} sudah kedaluwarsa " . abs($sisaHari) . " hari lalu! Masih tersisa {$batch->stok_toko} pcs di toko. SEGERA BUANG/TARIK BARANG INI! (Kedaluwarsa: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . ")")
                    ->sendToDatabase($admin);
            } else {
                Notification::make()
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->title("Peringatan: {$batch->product->nama} (Batch: {$batch->batch_code})")
                    ->body("Terdapat {$batch->stok_toko} pcs di stok toko yang mendekati kedaluwarsa (Sisa {$sisaHari} hari). Segera periksa barang!")
                    ->sendToDatabase($admin);
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

            foreach ($batch->consignmentStocks as $titipan) {
                if ($titipan->stok_titipan <= 0) continue;

                if ($sisaHari < 0) {
                    Notification::make()
                        ->danger()
                        ->icon('heroicon-o-x-circle')
                        ->title("🚨 DARURAT: Tarik {$batch->product->nama} dari {$titipan->partner->nama_apotek}!")
                        ->body("Batch {$batch->batch_code} sudah kedaluwarsa " . abs($sisaHari) . " hari lalu! Masih ada {$titipan->stok_titipan} pcs di apotek. SEGERA TARIK BARANG! (Kedaluwarsa: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . ")")
                        ->sendToDatabase($admin);
                } else {
                    Notification::make()
                        ->warning()
                        ->icon('heroicon-o-truck')
                        ->title("Tarik Barang dari {$titipan->partner->nama_apotek}")
                        ->body("Produk {$batch->product->nama} (Batch: {$batch->batch_code}) sebanyak {$titipan->stok_titipan} pcs mendekati kedaluwarsa (Sisa {$sisaHari} hari, Tgl: " . Carbon::parse($batch->tanggal_kedaluwarsa)->format('d M Y') . "). Segera lakukan penarikan!")
                        ->sendToDatabase($admin);
                }
            }
        }

        $this->info('Pemeriksaan kedaluwarsa selesai dilakukan.');
    }
}
