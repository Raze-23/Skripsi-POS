<?php

namespace App\Services;

use App\Models\ConsignmentDelivery;
use App\Models\ConsignmentStock;
use App\Models\ProductBatch;
use App\Models\ProductRequest;
use DomainException;
use Illuminate\Support\Facades\DB;

class ConsignmentDeliveryService
{
    public static function discountedPrice(int $basePrice, float|int|string|null $discountPercent): int
    {
        $discount = self::normalizeDiscount($discountPercent);

        return max(0, (int) round($basePrice * (1 - ($discount / 100))));
    }

    public static function normalizeDiscount(float|int|string|null $discountPercent): float
    {
        $discount = round((float) ($discountPercent ?? 0), 2);

        if ($discount < 0 || $discount > 100) {
            throw new DomainException('Diskon harus berada di antara 0% sampai 100%.');
        }

        return $discount;
    }

    public function deliver(
        int $partnerId,
        int $productBatchId,
        int $salesId,
        int $quantity,
        float|int|string|null $discountPercent = 0,
        ?int $productRequestId = null,
    ): ConsignmentDelivery {
        if ($quantity < 1) {
            throw new DomainException('Jumlah produk yang dikirim minimal 1 pcs.');
        }

        $discount = self::normalizeDiscount($discountPercent);

        return DB::transaction(function () use (
            $partnerId,
            $productBatchId,
            $salesId,
            $quantity,
            $discount,
            $productRequestId,
        ) {
            $batch = ProductBatch::query()
                ->with('product')
                ->lockForUpdate()
                ->find($productBatchId);

            if (! $batch) {
                throw new DomainException('Batch produk tidak ditemukan.');
            }

            if ((int) $batch->stok_toko < $quantity) {
                throw new DomainException("Stok toko tidak cukup. Tersedia {$batch->stok_toko} pcs.");
            }

            $request = null;

            if ($productRequestId) {
                $request = ProductRequest::query()->lockForUpdate()->find($productRequestId);

                if (! $request || $request->tipe_request !== 'restok_apotek') {
                    throw new DomainException('Request Mitra tidak ditemukan atau tidak valid.');
                }

                if ($request->status !== ProductRequest::STATUS_DIPROSES) {
                    throw new DomainException('Hanya request berstatus Diproses yang dapat dikirim.');
                }

                if ((int) $request->partner_id !== $partnerId || (int) $request->product_id !== (int) $batch->product_id) {
                    throw new DomainException('Apotek atau produk pada pengiriman tidak sesuai dengan request.');
                }

                if ((int) $request->jumlah !== $quantity) {
                    throw new DomainException("Jumlah pengiriman harus sama dengan request, yaitu {$request->jumlah} pcs.");
                }

                $discount = self::normalizeDiscount($request->diskon_persen);
            }

            $unitPrice = self::discountedPrice((int) $batch->product->harga_jual, $discount);

            $stock = ConsignmentStock::query()
                ->where('partner_id', $partnerId)
                ->where('product_batch_id', $batch->id)
                ->lockForUpdate()
                ->first();

            if ($stock && $stock->stok_titipan > 0) {
                $currentUnitPrice = $stock->harga_satuan ?? $batch->product->harga_jual;
                $currentDiscount = (float) ($stock->diskon_persen ?? 0);

                if ((int) $currentUnitPrice !== $unitPrice || abs($currentDiscount - $discount) >= 0.005) {
                    throw new DomainException(
                        'Batch ini masih memiliki stok titipan dengan harga diskon berbeda. '
                        .'Gunakan batch lain atau selesaikan penarikan stok lama terlebih dahulu.'
                    );
                }
            }

            $updated = ProductBatch::query()
                ->whereKey($batch->id)
                ->where('stok_toko', '>=', $quantity)
                ->decrement('stok_toko', $quantity);

            if ($updated !== 1) {
                throw new DomainException('Stok berubah saat diproses. Silakan periksa stok lalu coba lagi.');
            }

            if (! $stock) {
                $stock = new ConsignmentStock([
                    'partner_id' => $partnerId,
                    'product_batch_id' => $batch->id,
                    'stok_titipan' => 0,
                ]);
            }

            $stock->fill([
                'sales_id' => $salesId,
                'diskon_persen' => $discount,
                'harga_satuan' => $unitPrice,
            ]);
            $stock->stok_titipan = (int) $stock->stok_titipan + $quantity;
            $stock->save();

            $delivery = ConsignmentDelivery::create([
                'product_request_id' => $request?->id,
                'partner_id' => $partnerId,
                'product_batch_id' => $batch->id,
                'sales_id' => $salesId,
                'jumlah' => $quantity,
                'diskon_persen' => $discount,
                'harga_satuan' => $unitPrice,
            ]);

            if ($request) {
                $request->update([
                    'diskon_persen' => $discount,
                    'harga_satuan' => $unitPrice,
                    'status' => ProductRequest::STATUS_SELESAI,
                ]);
            }

            return $delivery;
        });
    }
}
