<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProduct extends LogsModel
{
    public $fillable = [
        'order_id',
        'product_id',
        'product_availability_id',
        'booking_date',
        'booking_time',
        'price',
        'quantity',
        'total',
        'quantity_full',
        'quantity_reduced',
        'quantity_free',
        'price_full',
        'price_reduced',
        'price_free',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'price_full' => 'decimal:2',
        'price_reduced' => 'decimal:2',
        'price_free' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productAvailability(): BelongsTo
    {
        return $this->belongsTo(ProductAvailability::class);
    }
}
