<?php

namespace App\Services\Invoicing;

use App\Contracts\InvoiceProvider;
use App\Models\Invoice;

/**
 * Fallback usato finché il provider reale (Fatture in Cloud) non ha credenziali.
 * Lascia la fattura salvata come `draft` così l'admin la vede in elenco e può reinviarla
 * quando il provider è configurato.
 */
class NullInvoiceProvider implements InvoiceProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function send(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => Invoice::STATUS_DRAFT,
            'provider' => $this->name(),
            'provider_error' => 'Provider e-invoicing non configurato: fattura salvata come draft.',
        ]);

        return $invoice;
    }
}
