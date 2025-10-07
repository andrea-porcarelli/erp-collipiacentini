<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function getFullNameAttribute() : string
    {
        return $this->name . ' ' . $this->surname;
    }
}
