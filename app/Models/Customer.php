<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends LogsModel
{
    public $fillable = [
        'country_id',
        'company_id',
        'name',
        'surname',
        'email',
        'phone',
        'prefix_phone',
        'address',
        'city',
        'zip_code',
        'fiscal_code',
        'birth_date',
        'privacy_accepted',
        'newsletter',
    ];

    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function orders() : HasMany {
        return $this->hasMany(Order::class);
    }

    public function getFullNameAttribute() : string
    {
        return sprintf('%s %s',$this->name, $this->surname);
    }

    public function getFullAddressAttribute() : string
    {
        return sprintf('%s, %s %s', $this->address, $this->zip_code, $this->city);
    }
}
