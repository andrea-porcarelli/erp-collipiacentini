<?php

namespace App\Models;

use App\Traits\HasLanguageContent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PartnerConsent extends LogsModel
{
    use HasLanguageContent;

    public const CODE_TERMS = 'terms_and_conditions';

    public $fillable = [
        'partner_id',
        'code',
        'is_required',
        'is_locked',
        'is_active',
        'expiry_days',
        'expiry_months',
        'expiry_years',
        'position',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_locked'   => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function customerConsents(): HasMany
    {
        return $this->hasMany(CustomerConsent::class);
    }

    public function computeExpiresAt(?Carbon $from = null): ?Carbon
    {
        $from = $from ?: Carbon::now();
        $d = (int) $this->expiry_days;
        $m = (int) $this->expiry_months;
        $y = (int) $this->expiry_years;
        if ($d === 0 && $m === 0 && $y === 0) {
            return null;
        }

        return $from->copy()
            ->addYears($y)
            ->addMonths($m)
            ->addDays($d);
    }
}
