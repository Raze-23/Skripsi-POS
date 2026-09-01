<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','kasir','owner','mitra') DEFAULT 'kasir'");

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('role')
                  ->constrained('partners')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn('partner_id');
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','kasir') DEFAULT 'kasir'");
    }
};
