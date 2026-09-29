<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $dataAwal = [
            ['nama' => 'Kunyit putih', 'harga_beli' => 6500, 'harga_jual' => 12000, 'stok' => 44, 'deskripsi' => 'Membantu menjaga kesehatan pencernaan dan daya tahan tubuh.', 'estimasi_masak' => 160, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Kunyit', 'harga_beli' => 6500, 'harga_jual' => 12000, 'stok' => 64, 'deskripsi' => 'Membantu menjaga pencernaan serta kebugaran tubuh.', 'estimasi_masak' => 160, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Jahe merah', 'harga_beli' => 6500, 'harga_jual' => 12000, 'stok' => 185, 'deskripsi' => 'Membantu menghangatkan tubuh dan menjaga daya tahan.', 'estimasi_masak' => 134, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Temulawak', 'harga_beli' => 7000, 'harga_jual' => 12000, 'stok' => 90, 'deskripsi' => 'Membantu menjaga nafsu makan dan fungsi pencernaan.', 'estimasi_masak' => 126, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Serbuk Temulawak 25gr', 'harga_beli' => 6000, 'harga_jual' => 10000, 'stok' => 2, 'deskripsi' => 'Membantu menjaga nafsu makan dan fungsi pencernaan.', 'estimasi_masak' => 75, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Kulit Manggis 25 gr', 'harga_beli' => 5500, 'harga_jual' => 10000, 'stok' => 10, 'deskripsi' => 'Sumber antioksidan untuk membantu menjaga kebugaran tubuh.', 'estimasi_masak' => 72, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Daun ungu 25 gr', 'harga_beli' => 5500, 'harga_jual' => 10000, 'stok' => 25, 'deskripsi' => 'Secara tradisional membantu menjaga kenyamanan pencernaan.', 'estimasi_masak' => 50, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Daun Jati Cina', 'harga_beli' => 6000, 'harga_jual' => 10000, 'stok' => 4, 'deskripsi' => 'Secara tradisional membantu melancarkan buang air besar.', 'estimasi_masak' => 152, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Daun Jati Londo 50 gr', 'harga_beli' => 5500, 'harga_jual' => 10000, 'stok' => 1, 'deskripsi' => 'Mendukung pengelolaan berat badan bersama pola hidup sehat.', 'estimasi_masak' => 80, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Daun salam 50 gr', 'harga_beli' => 6000, 'harga_jual' => 10000, 'stok' => 3, 'deskripsi' => 'Membantu menjaga metabolisme dan kesehatan pencernaan.', 'estimasi_masak' => 86, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Daun Sirsat 25 gr', 'harga_beli' => 5500, 'harga_jual' => 10000, 'stok' => 12, 'deskripsi' => 'Sumber antioksidan untuk membantu menjaga daya tahan tubuh.', 'estimasi_masak' => 71, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Tempuyung plus 40 gr', 'harga_beli' => 5500, 'harga_jual' => 10000, 'stok' => 26, 'deskripsi' => 'Secara tradisional membantu menjaga kesehatan saluran kemih.', 'estimasi_masak' => 82, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Jati Cina Celup isi 10', 'harga_beli' => 6500, 'harga_jual' => 12000, 'stok' => 66, 'deskripsi' => 'Secara tradisional membantu melancarkan buang air besar.', 'estimasi_masak' => 77, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Daun Insulin Celup isi 10', 'harga_beli' => 6500, 'harga_jual' => 12000, 'stok' => 93, 'deskripsi' => 'Membantu menjaga metabolisme gula bersama pola hidup sehat.', 'estimasi_masak' => 84, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Daun Kelor Celup isi 10', 'harga_beli' => 7000, 'harga_jual' => 12000, 'stok' => 11, 'deskripsi' => 'Sumber nutrisi dan antioksidan untuk menjaga kebugaran.', 'estimasi_masak' => 82, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Sarang Semut Celup isi 10', 'harga_beli' => 7000, 'harga_jual' => 12000, 'stok' => 16, 'deskripsi' => 'Sumber antioksidan untuk membantu menjaga daya tahan tubuh.', 'estimasi_masak' => 62, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Kunyit putih 20 kaps', 'harga_beli' => 8000, 'harga_jual' => 15000, 'stok' => 168, 'deskripsi' => 'Membantu menjaga kesehatan pencernaan dan daya tahan tubuh.', 'estimasi_masak' => 137, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Temulawak 20 kaps', 'harga_beli' => 8500, 'harga_jual' => 15000, 'stok' => 108, 'deskripsi' => 'Membantu menjaga nafsu makan dan fungsi pencernaan.', 'estimasi_masak' => 150, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Keladi Tikus 20 kaps', 'harga_beli' => 8500, 'harga_jual' => 15000, 'stok' => 34, 'deskripsi' => 'Secara tradisional membantu memelihara daya tahan tubuh.', 'estimasi_masak' => 137, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Zedoaria & Cur. Mangga isi 20', 'harga_beli' => 9000, 'harga_jual' => 15000, 'stok' => 97, 'deskripsi' => 'Membantu menjaga pencernaan dan kebugaran tubuh.', 'estimasi_masak' => 160, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Pasakbumi 20 kaps', 'harga_beli' => 8500, 'harga_jual' => 15000, 'stok' => 35, 'deskripsi' => 'Membantu menjaga stamina dan vitalitas tubuh.', 'estimasi_masak' => 147, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Sambiloto 20 kaps', 'harga_beli' => 9000, 'harga_jual' => 15000, 'stok' => 99, 'deskripsi' => 'Membantu memelihara daya tahan dan kebugaran tubuh.', 'estimasi_masak' => 126, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Kunyit Asam 610 ml', 'harga_beli' => 12000, 'harga_jual' => 18000, 'stok' => 74, 'deskripsi' => 'Minuman herbal yang menyegarkan dan membantu menjaga pencernaan.', 'estimasi_masak' => 91, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Jenang garut', 'harga_beli' => 8000, 'harga_jual' => 15000, 'stok' => 33, 'deskripsi' => 'Camilan lembut sebagai sumber energi yang mudah dinikmati.', 'estimasi_masak' => 39, 'tanggal_kedaluwarsa' => '2026-10-01'],
            ['nama' => 'Pathi Garut', 'harga_beli' => 5500, 'harga_jual' => 9000, 'stok' => 75, 'deskripsi' => 'Mudah dicerna dan membantu memenuhi kebutuhan energi.', 'estimasi_masak' => 32, 'tanggal_kedaluwarsa' => '2026-11-01'],
            ['nama' => 'Wedang Uwuh', 'harga_beli' => 6500, 'harga_jual' => 12000, 'stok' => 45, 'deskripsi' => 'Membantu menghangatkan tubuh dan menjaga kebugaran.', 'estimasi_masak' => 33, 'tanggal_kedaluwarsa' => '2026-10-01'],
        ];

        foreach ($dataAwal as $item) {$product = Product::create([
                'nama' => $item['nama'],
                'deskripsi' => $item['deskripsi'],
                'harga_beli' => $item['harga_beli'],
                'harga_jual' => $item['harga_jual'],
                'estimasi_masak' => $item['estimasi_masak'],
            ]);

            ProductBatch::create([
                'product_id' => $product->id,
                'stok_toko' => $item['stok'],
                'tanggal_kedaluwarsa' => $item['tanggal_kedaluwarsa'],
            ]);
        }
    }
}
