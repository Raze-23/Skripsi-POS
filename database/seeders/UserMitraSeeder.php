<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserMitraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apoteks = Partner::all();

        foreach ($apoteks as $apotek) {
            $email = Str::slug($apotek->nama_apotek) . '@mitra.com';

            User::updateOrCreate(
                ['email' => $email], 
                [
                    'name'       => $apotek->nama_apotek,
                    'password'   => Hash::make('mitra321'), 
                    'role'       => 'mitra',
                    'partner_id' => $apotek->id,
                ]
            );
        }
    }
}
