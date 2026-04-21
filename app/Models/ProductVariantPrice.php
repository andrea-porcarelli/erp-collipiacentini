<?php

namespace App\Models;

use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantPrice extends LogsModel
{
    use InvalidatesProductSeoCache;

    public function productSeoCacheIds(): array
    {
        $productId = $this->variant()->first()?->product_id
            ?? ProductVariant::whereKey($this->product_variant_id)->value('product_id');

        return array_filter([$productId]);
    }

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
