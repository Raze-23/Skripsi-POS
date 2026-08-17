<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    protected $fillable = ['nama', 'no_telp', 'is_active'];

    public function consignmentDeliveries()
    {
        return $this->hasMany(ConsignmentDelivery::class);
    }

    public function consignmentReturns()
    {
        return $this->hasMany(ConsignmentReturn::class);
    }
}