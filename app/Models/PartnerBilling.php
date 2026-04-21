<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerBilling extends LogsModel
{
    public $fillable = [
        'partner_id',
        'legal_name',
        'vat_number',
        'tax_code',
        'street_address',
        'postal_code',
        'city',
        'province',
        'country',
        'pec_email',
        'sdi_code',
        'iban',
        'tax_regime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
