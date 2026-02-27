<?php
namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends LogsModel
{
    public $fillable = [
        'partner_id',
        'category_id',
        'is_active',
        'iva',
        'category_code',
        'label',
    ];


    public function partner() : BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }
}
