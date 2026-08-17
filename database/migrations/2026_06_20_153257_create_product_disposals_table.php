<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_batch_id')->constrained()->cascadeOnDelete(); 
            $table->integer('jumlah');
            $table->string('alasan');
            $table->string('sumber')->default('Toko');
            $table->foreignId('consignment_return_id')->nullable()->constrained('consignment_returns')->cascadeOnDelete();
            
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('product_disposals');
    }
};
