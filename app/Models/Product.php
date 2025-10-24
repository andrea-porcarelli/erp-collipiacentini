<?php
namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends LogsModel
{
    public $fillable = [
        'partner_id',
        'category_id',
        'label',
        'description',
        'meta_title',
        'meta_description',
        'meta_key',
    ];


    public function partner() : BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function category() : BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
