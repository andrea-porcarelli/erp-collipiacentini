<?php
namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends LogsModel
{
    public $fillable = [
        'customer_id',
        'company_id',
        'order_number',
        'amount',
        'order_status',
    ];


    protected $casts = [
        'order_status' => OrderStatus::class,
    ];

    public function company() : BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
