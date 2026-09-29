<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank');
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->string('bank_tujuan')->nullable();
            $table->string('rekening_tujuan')->nullable();
            $table->string('pemilik_rekening_tujuan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('consignment_returns', fn (Blueprint $table) => $table->dropColumn(['bank_tujuan', 'rekening_tujuan', 'pemilik_rekening_tujuan']));
        Schema::dropIfExists('payment_accounts');
    }
};
