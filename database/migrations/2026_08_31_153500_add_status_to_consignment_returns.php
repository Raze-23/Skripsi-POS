<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->enum('status', ['menunggu_konfirmasi', 'selesai'])
                  ->default('selesai')
                  ->after('omzet_terbentuk');
        });
    }

    public function down(): void
    {
        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
