<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantPrice extends LogsModel
{
    public $fillable = [
        'product_variant_id',
        'label',
        'price',
        'vat_rate',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'vat_rate' => 'decimal:2',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
