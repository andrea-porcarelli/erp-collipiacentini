<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends LogsModel
{
    public $fillable = [
        'session_id',
        'customer_id',
        'company_id',
        'product_id',
        'product_availability_id',
        'date',
        'time',
        'quantity_full',
        'quantity_reduced',
        'quantity_free',
        'price_full',
        'price_reduced',
        'price_free',
        'total',
    ];

    protected $casts = [
        'date' => 'date',
        'price_full' => 'decimal:2',
        'price_reduced' => 'decimal:2',
        'price_free' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productAvailability(): BelongsTo
    {
        return $this->belongsTo(ProductAvailability::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->quantity_full + $this->quantity_reduced + $this->quantity_free;
    }

    public static function getBySession(?string $sessionId = null): ?self
    {
        $sessionId = $sessionId ?? session()->getId();
        return self::where('session_id', $sessionId)->first();
    }
}
