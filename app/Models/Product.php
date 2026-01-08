<?php
namespace App\Models;
use Carbon\Language;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Product extends LogsModel
{
    public $fillable = [
        'partner_id',
        'category_id',
        'is_active',
        'label',
        'duration',
        'product_type',
        'linked_product_ids'
    ];

    protected $casts = [
        'linked_product_ids' => 'array',
    ];


    public function partner() : BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function category() : BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function availabilities() : HasMany
    {
        return $this->hasMany(ProductAvailability::class);
    }

    public function getSharedAvailabilities()
    {
        // Ottieni gli ID dei prodotti collegati (incluso questo prodotto)
        $productIds = array_merge(
            [$this->id],
            $this->linked_product_ids ?? []
        );

        // Restituisci tutte le availabilities di questi prodotti
        return ProductAvailability::whereIn('product_id', $productIds)
            ->where('availability', '>', 0)
            ->whereDate('date', '>=', date('Y-m-d'))
            ->orderBy('date', 'ASC')
            ->get();
    }

    public function prices() : HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function contents() : MorphMany
    {
        return $this->morphMany(LanguageContent::class, 'entity');
    }

    public function content()
    {
        return $this->contents()
            ->whereHas('language', function($query) {
                $query->where('iso_code', app()->getLocale());
            })
            ->first();
    }

    public function media() : MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function cover() : MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('media_type', 'cover');
    }

    public function gallery() : MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('media_type', 'gallery');
    }

    public function getProductCodeAttribute() : string
    {
        return sprintf('%s-%s-%s%s', $this->partner->company->company_code, $this->category->category_code, $this->partner->partner_code, str_pad($this->id, 5, '0', STR_PAD_LEFT));
    }

    public function getLowestPriceAttribute() : string {
        return $this->prices()->orderBy('price', 'ASC')->first()->price ?? 0;
    }

    public function getIsAvailableAttribute() : bool {
        return $this->is_active && $this->availabilities()->whereDate('date', '>', date('Y-m-d'))->where('availability', '>', 0)->count();
    }

    public function getButtonAttribute() : string {
        return $this->is_available ? 'primary' : 'disabled';
    }

    public function getTypeAttribute() : string {
        return __('products.types.' . $this->product_type);
    }

    public function getIntroAttribute() : ?string {
        return $this->content()?->intro;
    }

    public function getDescriptionAttribute() : ?string {
        return $this->content()?->description;
    }

    public function getMetaTitleAttribute() : ?string {
        return $this->content()?->meta_title ?? $this->label;
    }

    public function getMetaDescriptionAttribute() : ?string {
        return $this->content()?->meta_description;
    }

    public function getMetaKeywordsAttribute() : ?string {
        return $this->content()?->meta_keywords;
    }

    public function getRouteAttribute() : string {
        try {
            $slugPartner = Str::slug($this->partner->partner_name ?? 'partner');
            $slugProduct = Str::slug($this->meta_title ?? $this->label ?? 'product');
            $productCode = $this->product_code;

            return route('booking.product', [
                'slugPartner' => $slugPartner,
                'slugProduct' => $slugProduct,
                'productCode' => $productCode,
            ]);
        } catch (\Exception $e) {
            return '#';
        }
    }

    public function getProductTagsAttribute() : ?string {
        return view('whitelabel.products.product_tags', ['product' => $this])->render();
    }
}
