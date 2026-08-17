<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderConsent extends Model
{
    public $fillable = [
        'order_id',
        'partner_consent_id',
        'partner_id',
        'accepted',
        'subscribed_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted' => 'boolean',
        'subscribed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function partnerConsent(): BelongsTo
    {
        return $this->belongsTo(PartnerConsent::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
