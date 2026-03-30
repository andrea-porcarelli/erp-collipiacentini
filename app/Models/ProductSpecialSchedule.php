<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecialSchedule extends Model
{
    protected $fillable = ['product_id', 'date', 'time', 'availability'];

    protected $casts = [
        'date'         => 'date',
        'availability' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
