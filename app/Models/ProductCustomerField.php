<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCustomerField extends LogsModel
{
    public $fillable = [
        'product_id',
        'customer_field_type_id',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fieldType(): BelongsTo
    {
        return $this->belongsTo(CustomerFieldType::class, 'customer_field_type_id');
    }
}
