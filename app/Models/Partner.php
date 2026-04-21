<?php
namespace App\Models;

use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Partner extends LogsModel
{
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
        'commission_presale_low',
        'commission_presale_high',
        'commission_presale_threshold',
        'commission_miticko_fixed',
        'commission_miticko_variable',
        'commission_payment',
    ];

    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function users() : HasMany
    {
        return $this->hasMany(User::class);
    }

    public function billing() : HasOne
    {
        return $this->hasOne(PartnerBilling::class);
    }

    public static function active() : Builder {
        return self::where('is_active', true);
    }

    public function getLabelAttribute() : string
    {
        return $this->partner_name ?? '';
    }

    public function active_products() : HasMany
    {
        return $this->products()->where('is_active', 1);
    }

    public function media() : MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function logo() : MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('media_type', 'logo');
    }

    public function cover() : MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('media_type', 'cover');
    }

    public function gallery() : MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('media_type', 'gallery');
    }

    public function resolvePresaleCommission(float $unitPrice) : float
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
