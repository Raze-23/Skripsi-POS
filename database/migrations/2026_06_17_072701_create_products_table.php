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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('sku')->unique();
            $table->string('foto')->nullable();
            $table->integer('estimasi_masak')->default(0);
            $table->integer('harga_beli');
            $table->integer('harga_jual');
            $table->integer('stok_toko')->default(0);
            $table->date('tanggal_kedaluwarsa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
