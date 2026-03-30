<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends LogsModel
{
    public $fillable = [
        'session_id',
        'customer_id',
        'company_id',
        'product_id',
        'date',
        'time',
        'slot_type',
        'slot_id',
        'applied_price_variation_id',
        'total',
    ];

    protected $casts = [
        'date'  => 'date',
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

    public function appliedPriceVariation(): BelongsTo
    {
        return $this->belongsTo(ProductPriceVariation::class, 'applied_price_variation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    public static function getBySession(?string $sessionId = null): ?self
    {
        $sessionId = $sessionId ?? session()->getId();
        return self::where('session_id', $sessionId)->first();
    }
}
