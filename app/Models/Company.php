<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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
        'token',
        'has_whitelabel',
    ];

    public function partners(): HasMany {
        return $this->hasMany(Partner::class);
    }

    public function active_partners(): HasMany {
        return $this->partners()->where('is_active', 1);
    }
}
