<?php
namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProduct extends LogsModel
{
    public $fillable = [
        'order_id',
        'product_id',
        'price',
        'quantity',
        'total',
    ];


    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
