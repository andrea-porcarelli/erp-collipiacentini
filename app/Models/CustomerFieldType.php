<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerFieldType extends LogsModel
{
    public $fillable = ['key', 'label', 'sort_order'];

    public function productFields(): HasMany
    {
        return $this->hasMany(ProductCustomerField::class);
    }
}
