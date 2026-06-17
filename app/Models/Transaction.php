<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
