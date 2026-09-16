<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saw_priority_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('current_rank');
            $table->unsignedSmallInteger('previous_rank')->nullable();
            $table->decimal('current_score', 7, 4);
            $table->decimal('previous_score', 7, 4)->nullable();
            $table->json('current_metrics');
            $table->json('previous_metrics')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'year']);
            $table->index(['year', 'current_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_priority_snapshots');
    }
};
