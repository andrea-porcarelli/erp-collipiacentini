<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends LogsModel
{
    public $fillable = [
        'product_id',
        'price',
        'adults',
        'guys',
        'fee_number',
        'fee_percent',
    ];

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
