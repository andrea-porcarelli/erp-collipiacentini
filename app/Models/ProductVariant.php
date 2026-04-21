<?php

namespace App\Models;

use App\Traits\HasLanguageContent;
use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends LogsModel
{
    use HasLanguageContent, InvalidatesProductSeoCache;

    public $fillable = [
        'product_id',
        'availability_id',
        'special_schedule_id',
        'label',
        'description',
        'max_quantity',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(ProductAvailability::class);
    }

    public function specialSchedule(): BelongsTo
    {
        return $this->belongsTo(ProductSpecialSchedule::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductVariantPrice::class);
    }

    public function getFullPriceAttribute(): float
    {
        return $this->prices()->sum('price');
    }
}
