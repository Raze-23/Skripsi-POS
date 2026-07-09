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
            $table->foreignId('product_batch_id')->constrained()->cascadeOnDelete(); // PERUBAHAN
            $table->integer('jumlah');
            $table->string('alasan');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('product_disposals');
    }
};
