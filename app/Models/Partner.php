<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_kerja_sama' => 'date',
        'is_active' => 'boolean',
    ];

    public function consignmentStocks()
    {
        return $this->hasMany(ConsignmentStock::class);
    }
    public function consignmentReturns()
    {
        return $this->hasMany(ConsignmentReturn::class);
    }
}
