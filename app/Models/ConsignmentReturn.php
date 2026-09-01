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
        'omzet_terbentuk',
        'status',
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
}