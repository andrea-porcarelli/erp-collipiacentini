<?php
namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends LogsModel
{
    public $fillable = [
        'is_active',
        'iva',
        'category_code',
        'label',
    ];

    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }
}
