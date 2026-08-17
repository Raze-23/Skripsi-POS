<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApotekSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $apoteks = [
            ['nama_apotek' => 'Titipan Sehat', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Abbiyu Farma', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Halmahera', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Drajidan Sehat', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Sumber Sehat', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Donohudan', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Dibal Farma', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Tohudan', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Jati Baru', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Pringgolayan', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Jumapolo', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Mitra Keluarga', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => '128 Kartasura', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Setyo Putro', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Relasi Jaya', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Empat Mata', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
            ['nama_apotek' => 'Murni Gading', 'alamat' => '-', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('partners')->insert($apoteks);
    }
}
