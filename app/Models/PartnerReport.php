<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerReport extends LogsModel
{
    public const MODE_MANUAL = 'manual';
    public const MODE_AUTO = 'auto';

    public $fillable = [
        'partner_id',
        'year',
        'month',
        'mode',
        'pdf_path',
        'triggered_by_user_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PartnerReportRecipient::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
