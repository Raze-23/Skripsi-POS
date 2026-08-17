<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentDelivery extends Model
{
    protected $fillable = [
        'partner_id',
        'product_batch_id',
        'sales_id', 
        'jumlah',
    ];

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
}