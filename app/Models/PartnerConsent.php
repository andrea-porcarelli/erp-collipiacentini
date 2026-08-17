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
        'version',
        'is_required',
        'is_locked',
        'is_active',
        'expiry_days',
        'expiry_months',
        'expiry_years',
        'position',
        'superseded_at',
        'superseded_by_id',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'superseded_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function orderConsents(): HasMany
    {
        return $this->hasMany(OrderConsent::class);
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    public function previousVersion()
    {
        return $this->hasOne(self::class, 'superseded_by_id');
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('superseded_at');
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
