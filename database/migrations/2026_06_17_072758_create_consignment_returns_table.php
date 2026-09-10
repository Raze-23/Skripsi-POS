<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->integer('terjual');
            $table->integer('qty_layak')->default(0);
            $table->integer('qty_rusak')->default(0);
            $table->integer('omzet_terbentuk')->default(0);
            $table->enum('status', ['menunggu_konfirmasi', 'selesai'])->default('selesai');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('consignment_returns');
    }
};
