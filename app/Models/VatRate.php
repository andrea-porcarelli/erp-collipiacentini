<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VatRate extends LogsModel
{
    public $fillable = [
        'code',
        'rate',
        'nature',
        'label',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function isNumeric(): bool
    {
        return $this->nature === null;
    }

    public function productVariantPrices(): HasMany
    {
        return $this->hasMany(ProductVariantPrice::class);
    }
}
