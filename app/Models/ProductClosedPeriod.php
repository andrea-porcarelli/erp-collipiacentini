<?php

namespace App\Models;

use App\Traits\InvalidatesProductSeoCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductClosedPeriod extends Model
{
    use InvalidatesProductSeoCache;

    protected $fillable = ['product_id', 'date_from', 'date_to', 'note'];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
