<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->string('status')->default('menunggu_konfirmasi')->change();
            $table->string('metode_pembayaran')->nullable();
            $table->string('bukti_pembayaran')->nullable();
            $table->string('nama_pengirim')->nullable();
            $table->string('bank_pengirim')->nullable();
            $table->string('referensi_transfer')->nullable();
            $table->timestamp('dibayar_pada')->nullable();
            $table->timestamp('diajukan_pada')->nullable();
            $table->timestamp('divalidasi_pada')->nullable();
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_penolakan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('divalidasi_oleh');
            $table->dropColumn(['metode_pembayaran', 'bukti_pembayaran', 'nama_pengirim', 'bank_pengirim', 'referensi_transfer', 'dibayar_pada', 'diajukan_pada', 'divalidasi_pada', 'catatan_penolakan']);
            $table->enum('status', ['menunggu_konfirmasi', 'selesai'])->default('selesai')->change();
        });
    }
};
