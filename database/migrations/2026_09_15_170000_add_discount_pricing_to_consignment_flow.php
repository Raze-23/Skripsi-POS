<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_requests', function (Blueprint $table) {
            $table->decimal('diskon_persen', 5, 2)->default(0)->after('jumlah');
            $table->unsignedBigInteger('harga_satuan')->nullable()->after('diskon_persen');
        });

        Schema::table('consignment_stocks', function (Blueprint $table) {
            $table->decimal('diskon_persen', 5, 2)->default(0)->after('stok_titipan');
            $table->unsignedBigInteger('harga_satuan')->nullable()->after('diskon_persen');
        });

        Schema::table('consignment_deliveries', function (Blueprint $table) {
            $table->foreignId('product_request_id')
                ->nullable()
                ->after('id')
                ->constrained('product_requests')
                ->nullOnDelete();
            $table->decimal('diskon_persen', 5, 2)->default(0)->after('jumlah');
            $table->unsignedBigInteger('harga_satuan')->nullable()->after('diskon_persen');
        });

        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->decimal('diskon_persen', 5, 2)->default(0)->after('qty_rusak');
            $table->unsignedBigInteger('harga_satuan')->nullable()->after('diskon_persen');
        });

        $productPrices = DB::table('products')->pluck('harga_jual', 'id');
        $batchPrices = DB::table('product_batches')
            ->get(['id', 'product_id'])
            ->mapWithKeys(fn ($batch) => [$batch->id => (int) ($productPrices[$batch->product_id] ?? 0)]);

        DB::table('product_requests')->get(['id', 'user_id', 'partner_id', 'product_id', 'tipe_request', 'status'])
            ->each(function ($request) use ($productPrices) {
                $partnerId = $request->partner_id;

                if ($request->tipe_request === 'restok_apotek' && ! $partnerId) {
                    $partnerId = DB::table('users')->where('id', $request->user_id)->value('partner_id');
                }

                DB::table('product_requests')->where('id', $request->id)->update([
                    'partner_id' => $partnerId,
                    'harga_satuan' => $request->tipe_request === 'restok_apotek' && $request->status === 'pending'
                        ? null
                        : (int) ($productPrices[$request->product_id] ?? 0),
                ]);
            });

        DB::table('consignment_stocks')->get(['id', 'product_batch_id'])
            ->each(fn ($stock) => DB::table('consignment_stocks')->where('id', $stock->id)->update([
                'harga_satuan' => (int) ($batchPrices[$stock->product_batch_id] ?? 0),
            ]));

        DB::table('consignment_deliveries')->get(['id', 'product_batch_id'])
            ->each(fn ($delivery) => DB::table('consignment_deliveries')->where('id', $delivery->id)->update([
                'harga_satuan' => (int) ($batchPrices[$delivery->product_batch_id] ?? 0),
            ]));

        DB::table('consignment_returns')->get(['id', 'product_batch_id', 'terjual', 'omzet_terbentuk'])
            ->each(function ($return) use ($batchPrices) {
                $unitPrice = (int) ($batchPrices[$return->product_batch_id] ?? 0);
                $revenue = (int) $return->omzet_terbentuk;

                if ($revenue === 0 && (int) $return->terjual > 0) {
                    $revenue = (int) $return->terjual * $unitPrice;
                }

                DB::table('consignment_returns')->where('id', $return->id)->update([
                    'harga_satuan' => $unitPrice,
                    'omzet_terbentuk' => $revenue,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('consignment_returns', function (Blueprint $table) {
            $table->dropColumn(['diskon_persen', 'harga_satuan']);
        });

        Schema::table('consignment_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_request_id');
            $table->dropColumn(['diskon_persen', 'harga_satuan']);
        });

        Schema::table('consignment_stocks', function (Blueprint $table) {
            $table->dropColumn(['diskon_persen', 'harga_satuan']);
        });

        Schema::table('product_requests', function (Blueprint $table) {
            $table->dropColumn(['diskon_persen', 'harga_satuan']);
        });
    }
};
