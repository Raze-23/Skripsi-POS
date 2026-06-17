<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal_kedaluwarsa' => 'date',
    ];

    public function consignmentStocks()
    {
        return $this->hasMany(ConsignmentStock::class);
    }
    public function consignmentReturns()
    {
        return $this->hasMany(ConsignmentReturn::class);
    }
    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
