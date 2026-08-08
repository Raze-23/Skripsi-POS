<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->restrictOnDelete();

            $table->dropForeign(['product_batch_id']);
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->restrictOnDelete();
        });

        Schema::table('consignment_stocks', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->restrictOnDelete();

            $table->dropForeign(['product_batch_id']);
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->restrictOnDelete();
        });

        Schema::table('product_disposals', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_disposals', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->cascadeOnDelete();
        });

        Schema::table('consignment_stocks', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->cascadeOnDelete();

            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
        });

        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->cascadeOnDelete();

            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
