<?php
namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends LogsModel
{
    public $fillable = [
        'customer_id',
        'partner_id',
        'order_number',
        'amount',
        'order_status',
        'customer_status',
        'customer_note',
        'internal_note',
        'stripe_payment_intent_id',
        'stripe_payment_method',
        'card_brand',
        'card_last4',
        'paid_at',
        'payment_error',
    ];


    protected $casts = [
        'order_status' => OrderStatus::class,
        'customer_status' => CustomerStatus::class,
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OrderParticipant::class);
    }

    public function getProductTimeAttribute() : string
    {
        return isset($this->orderProducts()->first()->booking_time) ? substr($this->orderProducts()->first()->booking_time, 0 , 5) : '';
    }

    public function getProductDataAttribute() : string
    {
        return $this->orderProducts()->first()->booking_date ?? '';
    }

    public function getProductLabelAttribute() : string
    {
        return $this->orderProducts()->first()->product->label ?? '';
    }
}
