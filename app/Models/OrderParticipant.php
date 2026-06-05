<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderParticipant extends LogsModel
{
    public $fillable = [
        'order_id',
        'order_product_item_id',
        'code',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrderParticipant $participant) {
            if (! empty($participant->code)) {
                return;
            }
            do {
                $code = Str::lower(Str::random(9));
            } while (static::where('code', $code)->exists());
            $participant->code = $code;
        });
    }

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
            'booked' => 'Prenotato',
            'checked_in' => 'Presentato',
            'no_show' => 'Non presentato',
            'cancelled' => 'Annullato',
            default => ucfirst((string) $this->status),
        };
    }
}
