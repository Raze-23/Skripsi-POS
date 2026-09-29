<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentReturn extends Model
{
    protected $fillable = [
        'partner_id',
        'product_batch_id',
        'sales_id',
        'terjual',
        'qty_layak',
        'qty_rusak',
        'diskon_persen',
        'harga_satuan',
        'omzet_terbentuk',
        'status',
        'metode_pembayaran', 'bukti_pembayaran', 'nama_pengirim', 'bank_pengirim',
        'referensi_transfer', 'dibayar_pada', 'diajukan_pada', 'divalidasi_pada',
        'divalidasi_oleh', 'catatan_penolakan',
        'bank_tujuan', 'rekening_tujuan', 'pemilik_rekening_tujuan',
    ];

    protected $casts = [
        'diskon_persen' => 'decimal:2',
        'harga_satuan' => 'integer',
        'omzet_terbentuk' => 'integer',
        'dibayar_pada' => 'datetime',
        'diajukan_pada' => 'datetime',
        'divalidasi_pada' => 'datetime',
    ];

    public function consignmentStock()
    {
        return $this->hasOne(ConsignmentStock::class, 'product_batch_id', 'product_batch_id')
            ->where('partner_id', $this->partner_id);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function productBatch()
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function sales()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function productDisposals()
    {
        return $this->hasMany(ProductDisposal::class, 'consignment_return_id');
    }

    public function resolvedUnitPrice(?ConsignmentStock $stock = null): int
    {
        return (int) ($this->harga_satuan
            ?? $stock?->harga_satuan
            ?? $this->productBatch?->product?->harga_jual
            ?? 0);
    }

    public function calculateRevenue(int $soldQuantity, ?ConsignmentStock $stock = null): int
    {
        return max(0, $soldQuantity) * $this->resolvedUnitPrice($stock);
    }
}
