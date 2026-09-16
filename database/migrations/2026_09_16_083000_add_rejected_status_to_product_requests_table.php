<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `product_requests` MODIFY `status` ENUM('pending', 'diproses', 'selesai', 'ditolak') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('product_requests')
            ->where('status', 'ditolak')
            ->update(['status' => 'pending']);

        DB::statement(
            "ALTER TABLE `product_requests` MODIFY `status` ENUM('pending', 'diproses', 'selesai') NOT NULL DEFAULT 'pending'"
        );
    }
};
