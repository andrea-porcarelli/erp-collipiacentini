<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log immutabile di ogni tentativo di invio di una Invoice verso il provider di
 * fatturazione (Fatture in Cloud). Una riga per ciascuna chiamata — successo o errore.
 *
 * Non estende LogsModel perché non ha senso avere activity log di righe già di tipo "log".
 */
class InvoiceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'attempt',
        'provider',
        'request_payload',
        'response_status',
        'response_body',
        'error_message',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_body' => 'array',
        'created_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
