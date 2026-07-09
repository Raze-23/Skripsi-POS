<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentStock extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['tanggal_titip' => 'date'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
    
    public function productBatch()
    {
        return $this->belongsTo(ProductBatch::class);
    }
}
