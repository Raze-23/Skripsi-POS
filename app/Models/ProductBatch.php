<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductBatch extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_kedaluwarsa' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'batch_code';
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($batch) {
            if (empty($batch->batch_code)) {
                $batch->batch_code = 'ATTIIN-' . date('Ym') . '-' . strtoupper(Str::random(4));
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productDisposals()
    {
        return $this->hasMany(ProductDisposal::class);
    }

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
