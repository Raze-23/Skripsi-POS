<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentDelivery extends Model
{
    protected $fillable = [
        'product_request_id',
        'partner_id',
        'product_batch_id',
        'sales_id',
        'jumlah',
        'diskon_persen',
        'harga_satuan',
    ];

    protected $casts = [
        'diskon_persen' => 'decimal:2',
        'harga_satuan' => 'integer',
    ];

    public function productRequest()
    {
        return $this->belongsTo(ProductRequest::class);
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
}
