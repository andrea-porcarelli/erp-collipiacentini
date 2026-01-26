<?php
namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends LogsModel
{
    public $fillable = [
        'customer_id',
        'company_id',
        'order_number',
        'amount',
        'order_status',
        'stripe_payment_intent_id',
        'stripe_payment_method',
        'paid_at',
        'payment_error',
    ];


    protected $casts = [
        'order_status' => OrderStatus::class,
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
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
