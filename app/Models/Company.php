<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends LogsModel
{
    public $fillable = [
        'company_name',
        'company_code',
        'phone',
        'email',
        'email_notify',
        'vat_number',
        'is_active',
    ];

    public function partners(): HasMany {
        return $this->hasMany(Partner::class);
    }
}
