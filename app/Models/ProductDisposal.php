<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDisposal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function productBatch()
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function consignmentReturn()
    {
        return $this->belongsTo(ConsignmentReturn::class);
    }
}
