<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SawPrioritySnapshot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'current_rank' => 'integer',
            'previous_rank' => 'integer',
            'current_score' => 'float',
            'previous_score' => 'float',
            'current_metrics' => 'array',
            'previous_metrics' => 'array',
            'changed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
