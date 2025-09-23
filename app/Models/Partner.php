<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
