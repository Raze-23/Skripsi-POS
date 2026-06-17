<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasir_id')->constrained('users')->restrictOnDelete();
            $table->integer('total_harga');
            $table->integer('diskon_persen')->default(0);
            $table->integer('nominal_bayar');
            $table->integer('nominal_kembalian');
            $table->enum('status', ['Selesai', 'Batal'])->default('Selesai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
