<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function productDisposals()
    {
        return $this->hasManyThrough(ProductDisposal::class, ProductBatch::class);
    }
}
