<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = ['id'];

    public function consignmentStocks()
    {
        return $this->hasMany(ConsignmentStock::class);
    }
    public function consignmentReturns()
    {
        return $this->hasMany(ConsignmentReturn::class);
    }
}
