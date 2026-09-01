<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRequest extends Model
{
    protected $fillable = [
        'user_id',
        'partner_id',
        'product_id',
        'tipe_request',
        'jumlah',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
