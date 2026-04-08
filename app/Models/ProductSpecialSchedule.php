<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSpecialSchedule extends Model
{
    protected $fillable = ['product_id', 'date', 'time'];

    protected $casts = [
        'date'         => 'date',
        'availability' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->HasMany(ProductVariant::class, 'special_schedule_id', 'id');
    }
}
