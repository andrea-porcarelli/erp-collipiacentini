<?php
namespace App\Models;
use App\Enums\ProductStatus;
use App\Traits\HasLanguageContent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'sub_category_id',
        'product_type',
        'occupancy',
        'occupancy_for_price',
        'free_occupancy_rule',
    ];

    protected $casts = [
        'is_active' => ProductStatus::class,
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
        // Restituisci tutte le availabilities di questi prodotti
        return ProductAvailability::where('product_id', $this->id)
            ->where('availability', '>', 0)
            ->whereDate('date', '>=', date('Y-m-d'))
            ->orderBy('date', 'ASC')
            ->get();
    }

    public function prices() : HasMany
    {
        return $this->hasMany(ProductPrice::class);
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
        return $this->morphMany(Media::class, 'mediable')->where('media_type', 'gallery');
    }

    public function getProductCodeAttribute() : string
    {
        if (!isset($this->category)) {
            return ' #---- ';
        }
        return isset($this->partner) ? sprintf('%s-%s-%s%s', $this->partner->company->company_code, $this->category->category_code, $this->partner->partner_code, str_pad($this->id, 5, '0', STR_PAD_LEFT)) : ' - ';
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
        return $this->contentField('intro');
    }

    public function getDescriptionAttribute() : ?string {
        return $this->contentField('description');
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
