<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends LogsModel
{
    public $fillable = [
        'company_id',
        'partner_name',
        'partner_code',
        'has_notify',
        'email_notify',
        'is_active',
    ];

    public function company() : BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function active_products() : HasMany
    {
        return $this->products()->where('is_active', 1);
    }
}
