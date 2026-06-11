<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerConsent extends Model
{
    public $fillable = [
        'customer_id',
        'partner_consent_id',
        'partner_id',
        'accepted',
        'subscribed_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted'      => 'boolean',
        'subscribed_at' => 'datetime',
        'expires_at'    => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
