<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProductItem extends LogsModel
{
    public $fillable = [
        'order_product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'partner_commission_presale_low',
        'partner_commission_presale_high',
        'partner_commission_presale_threshold',
        'partner_commission_miticko_fixed',
        'partner_commission_miticko_variable',
        'partner_commission_payment',
    ];

    protected $casts = [
        'unit_price'                            => 'decimal:2',
        'partner_commission_presale_low'        => 'decimal:2',
        'partner_commission_presale_high'       => 'decimal:2',
        'partner_commission_presale_threshold'  => 'decimal:2',
        'partner_commission_miticko_fixed'      => 'decimal:2',
        'partner_commission_miticko_variable'   => 'decimal:2',
        'partner_commission_payment'            => 'decimal:2',
    ];

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->unit_price * $this->quantity, 2);
    }
}
