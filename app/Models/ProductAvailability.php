<?php
namespace App\Models;

use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAvailability extends LogsModel
{
    use InvalidatesProductSeoCache;

    public $fillable = [
        'product_id',
        'day_of_week',
        'date',
        'time',
        'availability',
    ];

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants() : HasMany
    {
        return $this->hasMany(ProductVariant::class, 'availability_id');
    }

    public function generic_variants() : HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'product_id')
                    ->whereNull('availability_id')
                    ->whereNull('special_schedule_id');
    }
}
