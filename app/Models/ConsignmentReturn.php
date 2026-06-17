<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentReturn extends Model
{
    protected $guarded = ['id'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
