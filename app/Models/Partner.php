<?php

namespace App\Models;

use App\Traits\HasLanguageContent;
use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Partner extends LogsModel
{
    use HasLanguageContent;
    use InvalidatesProductSeoCache;

    public function productSeoCacheIds(): array
    {
        return Product::where('partner_id', $this->id)->pluck('id')->all();
    }

    public $fillable = [
        'partner_name',
        'partner_code',
        'has_notify',
        'email_notify',
        'phone_number',
        'structure_address',
        'is_active',
        'sale_method',
        'domain_name',
        'slug_name',
        'css_style',
        'commission_presale_low',
        'commission_presale_low_vat',
        'commission_presale_high',
        'commission_presale_high_vat',
        'commission_presale_threshold_high',
        'commission_presale_threshold_low',
        'commission_miticko_fixed',
        'commission_miticko_fixed_vat',
        'commission_miticko_variable',
        'commission_miticko_variable_vat',
        'commission_payment',
        'commission_payment_vat',
        'consents_enabled',
        'report_auto_send_enabled',
        'billing_managed_by',
        'billing_tool',
    ];

    protected $casts = [
        'consents_enabled' => 'boolean',
        'report_auto_send_enabled' => 'boolean',
    ];

    public const BILLING_MANAGED_BY_MITICKO = 'miticko';

    public const BILLING_MANAGED_BY_PARTNER = 'partner';

    public function isBillingManagedByMiticko(): bool
    {
        return $this->billing_managed_by === self::BILLING_MANAGED_BY_MITICKO;
    }

    public const CSS_STYLES = ['Miticko', 'Veleia', 'Vigoleno'];

    public const PAGES = [
        'contatti' => ['field' => 'contacts_content', 'title' => 'Contatti'],
        'privacy-policy' => ['field' => 'privacy_policy',   'title' => 'Privacy Policy'],
        'cookie-policy' => ['field' => 'cookie_policy',    'title' => 'Cookie Policy'],
        'termini-condizioni' => ['field' => 'terms_conditions', 'title' => 'Termini e Condizioni'],
    ];

    public function pageUrl(string $slug): string
    {
        $slugPath = ltrim($slug, '/');
        $domain = $this->domain_name ?: null;

        if ($this->sale_method === 'whitelabel_domain' && $domain) {
            $base = preg_match('#^https?://#i', $domain) ? rtrim($domain, '/') : 'https://'.rtrim($domain, '/');

            return $base.'/'.$slugPath;
        }

        if ($this->sale_method === 'whitelabel_no_domain' && $domain) {
            return 'https://miticko.com/'.trim($domain, '/').'/'.$slugPath;
        }

        return '/'.$slugPath;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function billing(): HasOne
    {
        return $this->hasOne(PartnerBilling::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PartnerConsent::class)->orderBy('position');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PartnerReport::class);
    }

    public function reportSubscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'partner_report_subscriptions')
            ->withPivot('auto_send')
            ->withTimestamps();
    }

    public static function active(): Builder
    {
        return self::where('is_active', true);
    }

    public function getLabelAttribute(): string
    {
        return $this->partner_name ?? '';
    }

    /**
     * Brand del design system applicato a questo partner.
     * Deriva dal campo `css_style` (vedi Partner::CSS_STYLES) mappato
     * in minuscolo sulle chiavi di config('design.brands').
     */
    public function getBrandAttribute(): string
    {
        $default = config('design.default_brand', 'miticko');
        $candidate = strtolower(trim((string) ($this->css_style ?? '')));
        $brands = array_keys(config('design.brands', []));

        return $candidate !== '' && in_array($candidate, $brands, true)
            ? $candidate
            : $default;
    }

    public function active_products(): HasMany
    {
        return $this->products()->where('is_active', 1);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function logo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('media_type', 'logo');
    }

    public function cover(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('media_type', 'cover');
    }

    public function gallery(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('media_type', 'gallery');
    }

    /**
     * Prevendita a due soglie con banda morta:
     *   - unit > threshold_high  → presale_high
     *   - unit < threshold_low   → presale_low
     *   - altrimenti             → 0 (nessuna prevendita)
     * Se una soglia è NULL, quel ramo è disattivato.
     */
    public function resolvePresaleCommission(float $unitPrice): float
    {
        $high = $this->commission_presale_threshold_high;
        $low = $this->commission_presale_threshold_low;

        if (! is_null($high) && $unitPrice > (float) $high) {
            return (float) ($this->commission_presale_high ?? 0);
        }

        if (! is_null($low) && $unitPrice < (float) $low) {
            return (float) ($this->commission_presale_low ?? 0);
        }

        return 0.0;
    }
}
