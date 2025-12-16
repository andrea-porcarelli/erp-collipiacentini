<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends LogsModel
{
    public $fillable = [
        'partner_id',
        'category_id',
        'is_active',
        'label',
        'description',
        'intro',
        'meta_title',
        'meta_description',
        'meta_key',
        'duration',
        'product_type'
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

    public function prices() : HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function getProductCodeAttribute() : string
    {
        return sprintf('%s-%s-%s%s', $this->partner->company->company_code, $this->category->category_code, $this->partner->partner_code, str_pad($this->id, 3, '0', STR_PAD_LEFT));
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
}
