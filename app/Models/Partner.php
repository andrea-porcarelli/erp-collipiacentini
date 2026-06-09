<?php

namespace App\Models;

use App\Traits\HasLanguageContent;
use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Builder;
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
        'is_active',
        'sale_method',
        'domain_name',
        'slug_name',
        'css_style',
        'commission_presale_low',
        'commission_presale_high',
        'commission_presale_threshold',
        'commission_miticko_fixed',
        'commission_miticko_variable',
        'commission_payment',
        'consents_enabled',
    ];

    protected $casts = [
        'consents_enabled' => 'boolean',
    ];

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

    public function resolvePresaleCommission(float $unitPrice): float
    {
        if (is_null($this->commission_presale_threshold)) {
            return 0.0;
        }

        $commission = $unitPrice < (float) $this->commission_presale_threshold
            ? $this->commission_presale_low
            : $this->commission_presale_high;

        return (float) ($commission ?? 0);
    }
}
