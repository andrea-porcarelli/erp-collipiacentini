<?php

namespace App\Contracts;

use App\Models\Invoice;

interface InvoiceProvider
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * Invia la fattura al provider. Aggiorna l'Invoice con provider_id/uuid/status/error.
     * Deve gestire i propri errori senza rilanciare: in caso di problemi imposta status=error
     * e provider_error, così l'admin può ritentare senza perdere la fattura.
     */
    public function send(Invoice $invoice): Invoice;
}
