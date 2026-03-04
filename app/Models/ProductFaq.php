<?php

namespace App\Models;

use App\Traits\HasLanguageContent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFaq extends LogsModel
{
    use HasLanguageContent;

    public $fillable = [
        'product_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
