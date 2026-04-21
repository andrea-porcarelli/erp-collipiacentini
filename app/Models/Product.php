<?php
namespace App\Models;
use App\Enums\ProductStatus;
use App\Models\ProductPriceVariation;
use App\Traits\HasLanguageContent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Product extends LogsModel
{
    use HasLanguageContent;
    public $fillable = [
        'partner_id',
        'category_id',
        'is_active',
        'label',
        'duration',
        'duration_days',
        'duration_hours',
        'duration_minutes',
        'product_type',
        'occupancy',
        'occupancy_for_price',
        'free_occupancy_rule',
    ];

    protected $casts = [];


    public function status(): ProductStatus
    {
        if (!$this->is_active) {
            return ProductStatus::PENDING;
        }

        return $this->is_available ? ProductStatus::ACTIVE : ProductStatus::UNAVAILABLE;
    }

    public function partner() : BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
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
        $service = app(\App\Services\ProductAvailabilityService::class);
        $now = now();
        $dates = [];

        for ($m = 0; $m < 12; $m++) {
            $ref = $now->copy()->addMonths($m);
            $days = $service->getAvailableDaysForMonth($this, $ref->year, $ref->month);
            $dates = array_merge($dates, $days);
        }

        return collect($dates)->map(fn($d) => (object)['date' => $d]);
    }

    public function priceVariations(): HasMany
    {
        return $this->hasMany(ProductPriceVariation::class);
    }

    public function links() : HasMany
    {
        return $this->hasMany(ProductLink::class);
    }

    public function faqs() : HasMany
    {
        return $this->hasMany(ProductFaq::class);
    }

    public function relatedProducts() : HasMany
    {
        return $this->hasMany(ProductRelated::class);
    }

    public function customerFields() : HasMany
    {
        return $this->hasMany(ProductCustomerField::class);
    }

    public function variants() : HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
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
        return $this->morphMany(Media::class, 'mediable')->where('media_type', 'gallery')->orderBy('sort_order');
    }

    public function specialSchedules(): HasMany
    {
        return $this->hasMany(ProductSpecialSchedule::class)->orderBy('date')->orderBy('time');
    }

    public function closedPeriods(): HasMany
    {
        return $this->hasMany(ProductClosedPeriod::class)->orderBy('date_from');
    }

    public function getProductCodeAttribute() : string
    {
        if (!isset($this->category)) {
            return ' #---- ';
        }
        return isset($this->partner) ? sprintf('%s-%s%s', $this->category->category_code, $this->partner->partner_code, str_pad($this->id, 5, '0', STR_PAD_LEFT)) : ' - ';
    }

    public function getLowestPriceAttribute() : string {
        return ProductVariantPrice::whereHas('variant', fn($q) => $q->where('product_id', $this->id))
            ->orderBy('price', 'ASC')
            ->value('price') ?? 0;
    }

    public function getLowestPriceWithCommissionAttribute() : float {
        $base = (float) $this->lowest_price;
        return $base + ($this->partner?->resolvePresaleCommission($base) ?? 0);
    }

    public function getIsAvailableAttribute() : bool {
        if (!$this->is_active) {
            return false;
        }
        // Has weekly template slots
        if ($this->availabilities()->whereNotNull('day_of_week')->exists()) {
            return true;
        }
        // Has future special schedule slots
        return $this->specialSchedules()->where('date', '>=', date('Y-m-d'))->exists();
    }

    public function getButtonAttribute() : string {
        return $this->is_available ? 'primary' : 'disabled';
    }

    public function getTypeAttribute() : string {
        return __('products.types.' . $this->product_type);
    }

    public function getIntroAttribute() : ?string {
        return $this->contentField('description');
    }

    public function getDescriptionAttribute() : ?string {
        return $this->contentField('long_description');
    }

    public function getMetaTitleAttribute() : ?string {
        return $this->contentField('meta_title') ?? $this->label;
    }

    public function getMetaDescriptionAttribute() : ?string {
        return $this->contentField('meta_description');
    }

    public function getMetaKeywordsAttribute() : ?string {
        return $this->contentField('meta_keywords');
    }

    public function getRouteAttribute() : string {
        try {
            $slugProduct = Str::slug($this->meta_title ?? $this->label ?? 'product');
            $productCode = $this->product_code;

            return route('booking.product', [
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

    public function getAvailabilityDaysAttribute() : array
    {
        return $this->availabilities()
            ->whereNotNull('day_of_week')
            ->distinct()
            ->pluck('day_of_week')
            ->values()
            ->toArray();
    }
}
