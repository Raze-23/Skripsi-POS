<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentStock extends Model
{
    protected $guarded = ['id'];

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
