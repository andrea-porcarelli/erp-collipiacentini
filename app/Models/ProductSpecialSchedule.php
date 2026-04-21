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

    public function generic_variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'product_id')
                    ->whereNull('availability_id')
                    ->whereNull('special_schedule_id');
    }
}
