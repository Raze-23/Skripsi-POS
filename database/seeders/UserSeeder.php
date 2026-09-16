<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@attiin.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('adminattiin321'),
                'role' => 'admin',
                'status' => 'aktif',
                'partner_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@attiin.com'],
            [
                'name' => 'owner',
                'password' => Hash::make('ownerattiin321'),
                'role' => 'owner',
                'status' => 'aktif',
                'partner_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'kasir@attiin.com'],
            [
                'name' => 'kasir',
                'password' => Hash::make('kasirattiin321'),
                'role' => 'kasir',
                'status' => 'aktif',
                'partner_id' => null,
            ]
        );
    }
}
