<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'status')) {
            if (DB::connection()->getDriverName() !== 'mysql') {
                return;
            }

            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('pending', 'menunggu_konfirmasi', 'aktif', 'ditolak') NOT NULL DEFAULT 'aktif'");

            DB::table('users')
                ->where('status', 'pending')
                ->update(['status' => 'menunggu_konfirmasi']);

            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('menunggu_konfirmasi', 'aktif', 'ditolak') NOT NULL DEFAULT 'aktif'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['menunggu_konfirmasi', 'aktif', 'ditolak'])
                ->default('aktif')
                ->after('partner_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'status')) {
            if (DB::connection()->getDriverName() !== 'mysql') {
                return;
            }

            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('pending', 'menunggu_konfirmasi', 'aktif', 'ditolak') NOT NULL DEFAULT 'aktif'");

            DB::table('users')
                ->where('status', 'menunggu_konfirmasi')
                ->update(['status' => 'pending']);

            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('pending', 'aktif', 'ditolak') NOT NULL DEFAULT 'aktif'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
