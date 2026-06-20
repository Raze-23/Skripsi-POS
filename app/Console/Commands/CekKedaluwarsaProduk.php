<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CekKedaluwarsaProduk extends Command
{
    protected $signature = 'app:cek-kedaluwarsa';
    protected $description = 'Peringatan dini otomatis untuk produk kedaluwarsa di toko dan mitra';

    public function handle()
    {
        $admin = User::first();
        if (!$admin) {
            $this->error('Admin belum ada di database.');
            return;
        }
        $hariIni = Carbon::now();

        // 1. CEK STOK TOKO (Gudang Utama) - Kedaluwarsa <= 7 Hari
        $batasToko = $hariIni->copy()->addDays(7);
        $produkGudangKritis = Product::where('stok_toko', '>', 0)
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', $batasToko)
            ->get();
        foreach ($produkGudangKritis as $produk) {
            $sisaHari = $hariIni->diffInDays(Carbon::parse($produk->tanggal_kedaluwarsa), false);
            $status = $sisaHari < 0 ? "TELAH KEDALUWARSA" : "Sisa {$sisaHari} Hari";
            Notification::make()
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->title("Bahaya Gudang: {$produk->nama}")
                ->body("Terdapat {$produk->stok_toko} pcs di stok toko utama yang mendekati kedaluwarsa ({$status}). Segera periksa fisik barang!")
                ->sendToDatabase($admin);
        }

        // 2. CEK STOK MITRA (Barang Dititipkan) - Kedaluwarsa <= 30 Hari
        $batasMitra = $hariIni->copy()->addDays(30);
        $produkMitraKritis = Product::whereHas('consignmentStocks', function ($query) {
                $query->where('stok_titipan', '>', 0);
            })
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', $batasMitra)
            ->with('consignmentStocks.partner')
            ->get();
        foreach ($produkMitraKritis as $produk) {
            foreach ($produk->consignmentStocks as $titipan) {
                // Pastikan barang di apotek tersebut masih ada
                if ($titipan->stok_titipan > 0) {
                    Notification::make()
                        ->warning()
                        ->icon('heroicon-o-truck')
                        ->title("Tarik Barang dari {$titipan->partner->nama_apotek}")
                        ->body("Produk {$produk->nama} sebanyak {$titipan->stok_titipan} pcs di apotek ini mendekati masa kedaluwarsa (Tgl: " . Carbon::parse($produk->tanggal_kedaluwarsa)->format('d M Y') . "). Lakukan penarikan secepatnya!")
                        ->sendToDatabase($admin);
                }
            }
        }
        $this->info('Pemeriksaan kedaluwarsa selesai dilakukan.');
    }
}
