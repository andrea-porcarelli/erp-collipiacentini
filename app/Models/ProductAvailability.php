<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAvailability extends LogsModel
{
    public $fillable = [
        'product_id',
        'date',
        'time',
        'availability',
    ];

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
