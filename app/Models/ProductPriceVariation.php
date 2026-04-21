<?php

namespace App\Models;

use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceVariation extends LogsModel
{
    use InvalidatesProductSeoCache;

    protected $fillable = [
        'product_id',
        'date_from',
        'date_to',
        'direction',
        'value',
        'unit',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
        'value'     => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUnitLabelAttribute(): string
    {
        return $this->unit === 'percent' ? '%' : '€';
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === 'decrement' ? ' - ' : ' + ';
    }
}
