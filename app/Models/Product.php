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

    public function getProductCodeAttribute() : string
    {
        return sprintf('%s-%s-%s%s', $this->partner->company->company_code, $this->category->category_code, $this->partner->partner_code, str_pad($this->id, 3, '0', STR_PAD_LEFT));
    }
}
