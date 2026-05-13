<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderParticipant extends LogsModel
{
    public $fillable = [
        'order_id',
        'order_product_item_id',
        'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProductItem(): BelongsTo
    {
        return $this->belongsTo(OrderProductItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'booked'    => 'Prenotato',
            'checked_in'=> 'Presentato',
            'no_show'   => 'Non presentato',
            'cancelled' => 'Annullato',
            default     => ucfirst((string) $this->status),
        };
    }
}
