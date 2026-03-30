<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderProduct extends LogsModel
{
    public $fillable = [
        'order_id',
        'product_id',
        'booking_date',
        'booking_time',
        'slot_type',
        'slot_id',
        'applied_price_variation_id',
        'price',
        'quantity',
        'total',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'price'        => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function appliedPriceVariation(): BelongsTo
    {
        return $this->belongsTo(ProductPriceVariation::class, 'applied_price_variation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderProductItem::class);
    }
}
