<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends LogsModel
{
    public const RECIPIENT_PARTNER = 'partner';

    public const RECIPIENT_CUSTOMER = 'customer';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ERROR = 'error';

    public $fillable = [
        'order_id',
        'recipient_type',
        'partner_id',
        'recipient_name',
        'recipient_vat_number',
        'recipient_tax_code',
        'recipient_address',
        'recipient_postal_code',
        'recipient_city',
        'recipient_province',
        'recipient_country',
        'recipient_email',
        'recipient_pec',
        'recipient_sdi_code',
        'number',
        'lines',
        'total',
        'currency',
        'status',
        'provider',
        'provider_id',
        'provider_uuid',
        'provider_error',
        'provider_payload',
        'emitted_at',
        'emitted_by_user_id',
    ];

    protected $casts = [
        'lines' => 'array',
        'total' => 'decimal:2',
        'emitted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function emittedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'emitted_by_user_id');
    }
}
