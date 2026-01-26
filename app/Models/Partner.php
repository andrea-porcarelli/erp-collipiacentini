<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Partner extends LogsModel
{
    public $fillable = [
        'company_id',
        'partner_name',
        'partner_code',
        'has_notify',
        'email_notify',
        'is_active',
    ];

    public function company() : BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
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
}
