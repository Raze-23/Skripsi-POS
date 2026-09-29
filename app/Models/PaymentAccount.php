<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    protected $fillable = ['bank', 'account_number', 'account_name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
