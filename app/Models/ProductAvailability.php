<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAvailability extends LogsModel
{
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

    public function variants() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class, 'availability_id');
    }
}
