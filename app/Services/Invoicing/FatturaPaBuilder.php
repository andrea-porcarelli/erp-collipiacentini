<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;
use DateTime;
use FatturaElettronicaPhp\FatturaElettronica\Address;
use FatturaElettronicaPhp\FatturaElettronica\Customer;
use FatturaElettronicaPhp\FatturaElettronica\DigitalDocument;
use FatturaElettronicaPhp\FatturaElettronica\DigitalDocumentInstance;
use FatturaElettronicaPhp\FatturaElettronica\Enums\DocumentType;
use FatturaElettronicaPhp\FatturaElettronica\Enums\TransmissionFormat;
use FatturaElettronicaPhp\FatturaElettronica\Enums\VatNature;
use FatturaElettronicaPhp\FatturaElettronica\Line;
use FatturaElettronicaPhp\FatturaElettronica\Supplier;

/**
 * Costruisce l'XML FatturaPA (SdI compliance) a partire da una Invoice del sistema.
 *
 * Per fatture con `recipient_type = partner` → Cedente = Miticko (config/fatturapa.php),
 * Cessionario = Partner (PartnerBilling).
 * Per `recipient_type = customer` → Cedente = Partner (PartnerBilling), Cessionario =
 * cliente finale (dati snapshot su Invoice.recipient_*).
 */
class FatturaPaBuilder
{
    public function build(Invoice $invoice): string
    {
        $invoice->loadMissing(['partner.billing', 'order.partner.billing']);

        $document = (new DigitalDocument())
            ->setTransmissionFormat($this->transmissionFormat())
            ->setCountryCode('IT')
            ->setSendingId($this->sendingId($invoice))
            ->setCustomerSdiCode($this->sdiCode($invoice))
            ->setSupplier($this->buildSupplier($invoice))
            ->setCustomer($this->buildCustomer($invoice));

        if ($pec = $invoice->recipient_pec) {
            $document->setCustomerPec($pec);
        }

        if ($senderVat = $this->config('miticko.sender_vat_id')) {
            $document->setSenderVatId($senderVat);
        }

        $instance = (new DigitalDocumentInstance())
            ->setDocumentNumber($invoice->number ?: (string) $invoice->id)
            ->setDocumentDate($invoice->emitted_at?->toDateTime() ?? new DateTime())
            ->setDocumentType(DocumentType::TD01()) // Fattura (TD04 = nota di credito, non gestita qui)
            ->setCurrency($invoice->currency ?: 'EUR')
            ->setDocumentTotal((float) $invoice->total);

        foreach ($this->buildLines($invoice) as $line) {
            $instance->addLine($line);
        }

        $document->addDigitalDocumentInstance($instance);

        return $document->serialize()->asXML();
    }

    /**
     * Nome file FatturaPA conforme SdI: IT{PartitaIVACedente}_{progressivo5CharBase36}.xml.
     * Il progressivo deve essere univoco per cedente — uso l'ID invoice come fonte.
     */
    public function filename(Invoice $invoice): string
    {
        $supplierVat = $this->supplierVatNumber($invoice) ?: '00000000000';
        $counter = str_pad(base_convert((string) $invoice->id, 10, 36), 5, '0', STR_PAD_LEFT);

        return sprintf('IT%s_%s.xml', $supplierVat, strtoupper($counter));
    }

    private function buildSupplier(Invoice $invoice): Supplier
    {
        if ($invoice->recipient_type === Invoice::RECIPIENT_PARTNER) {
            // Cedente = Miticko (fattura B2B di commissioni verso il Partner)
            $supplier = (new Supplier())
                ->setVatNumber((string) $this->config('miticko.vat_number'))
                ->setFiscalCode((string) ($this->config('miticko.fiscal_code') ?: $this->config('miticko.vat_number')))
                ->setOrganization((string) $this->config('miticko.organization'))
                ->setCountryCode((string) $this->config('miticko.country_code', 'IT'))
                ->setAddress($this->mitickoAddress());

            if ($regime = $this->config('miticko.tax_regime')) {
                $supplier->setTaxRegime($regime);
            }

            return $supplier;
        }

        // Cedente = Partner (fattura verso il cliente finale)
        $billing = $invoice->order?->partner?->billing;
        $supplier = (new Supplier())
            ->setVatNumber((string) ($billing?->vat_number ?: ''))
            ->setFiscalCode((string) ($billing?->tax_code ?: $billing?->vat_number ?: ''))
            ->setOrganization((string) ($billing?->legal_name ?: $invoice->order?->partner?->partner_name ?: ''))
            ->setCountryCode((string) ($billing?->country ?: 'IT'))
            ->setAddress($this->partnerBillingAddress($billing));

        if ($billing?->tax_regime) {
            $supplier->setTaxRegime($billing->tax_regime);
        }

        return $supplier;
    }

    private function buildCustomer(Invoice $invoice): Customer
    {
        $customer = (new Customer())
            ->setCountryCode((string) ($invoice->recipient_country ?: 'IT'))
            ->setOrganization((string) ($invoice->recipient_name ?: ''))
            ->setAddress($this->recipientAddress($invoice));

        if ($invoice->recipient_vat_number) {
            $customer->setVatNumber((string) $invoice->recipient_vat_number);
        }
        if ($invoice->recipient_tax_code) {
            $customer->setFiscalCode((string) $invoice->recipient_tax_code);
        }

        return $customer;
    }

    /**
     * @return array<int,Line>
     */
    private function buildLines(Invoice $invoice): array
    {
        $lines = [];
        foreach (($invoice->lines ?? []) as $i => $raw) {
            $qty = (float) ($raw['quantity'] ?? 1);
            $unit = (float) ($raw['unit_amount'] ?? 0);
            $total = (float) ($raw['total'] ?? ($qty * $unit));
            $vatRate = $raw['vat_rate'] ?? null;

            $line = (new Line())
                ->setNumber($i + 1)
                ->setDescription((string) ($raw['label'] ?? 'Voce'))
                ->setQuantity($qty)
                ->setUnitPrice($unit)
                ->setTotal($total);

            // Mapping aliquota numerica vs natura di esenzione (SdI 1.2).
            if (is_string($vatRate) && preg_match('/^N/i', $vatRate)) {
                $line->setTaxPercentage(0);
                $nature = $this->mapVatNature(strtoupper($vatRate));
                if ($nature) {
                    $line->setVatNature($nature);
                }
            } else {
                $line->setTaxPercentage((float) ($vatRate ?? 0));
            }

            $lines[] = $line;
        }

        return $lines;
    }

    private function mitickoAddress(): Address
    {
        $a = (array) $this->config('miticko.address', []);

        return (new Address())
            ->setStreet((string) ($a['street'] ?? ''))
            ->setStreetNumber((string) ($a['street_number'] ?? ''))
            ->setZip((string) ($a['zip'] ?? ''))
            ->setCity((string) ($a['city'] ?? ''))
            ->setState((string) ($a['province'] ?? ''))
            ->setCountryCode((string) ($a['country_code'] ?? 'IT'));
    }

    private function partnerBillingAddress(?\App\Models\PartnerBilling $billing): Address
    {
        return (new Address())
            ->setStreet((string) ($billing?->street_address ?? ''))
            ->setZip((string) ($billing?->postal_code ?? ''))
            ->setCity((string) ($billing?->city ?? ''))
            ->setState((string) ($billing?->province ?? ''))
            ->setCountryCode((string) ($billing?->country ?? 'IT'));
    }

    private function recipientAddress(Invoice $invoice): Address
    {
        return (new Address())
            ->setStreet((string) ($invoice->recipient_address ?? ''))
            ->setZip((string) ($invoice->recipient_postal_code ?? ''))
            ->setCity((string) ($invoice->recipient_city ?? ''))
            ->setState((string) ($invoice->recipient_province ?? ''))
            ->setCountryCode((string) ($invoice->recipient_country ?? 'IT'));
    }

    private function sendingId(Invoice $invoice): string
    {
        // ProgressivoInvio: alfanumerico max 10 — uso l'id invoice.
        return substr((string) $invoice->id, -10);
    }

    private function sdiCode(Invoice $invoice): string
    {
        // Default convenzionale per clienti privati senza SdI/PEC: "0000000".
        return $invoice->recipient_sdi_code ?: '0000000';
    }

    private function supplierVatNumber(Invoice $invoice): ?string
    {
        if ($invoice->recipient_type === Invoice::RECIPIENT_PARTNER) {
            return (string) $this->config('miticko.vat_number');
        }

        return $invoice->order?->partner?->billing?->vat_number;
    }

    private function transmissionFormat(): TransmissionFormat
    {
        return (string) $this->config('transmission_format', 'FPR12') === 'FPA12'
            ? TransmissionFormat::FPA12()
            : TransmissionFormat::FPR12();
    }

    /**
     * Mappa un codice natura stringa ("N1", "N2.1", …) sul corrispondente enum della libreria.
     * Ritorna null se il codice non è riconosciuto (il builder lascerà la riga senza natura).
     */
    private function mapVatNature(string $code): ?VatNature
    {
        $method = str_replace('.', '_', $code); // "N6.9" → "N6_9"
        if (method_exists(VatNature::class, $method)) {
            return VatNature::$method();
        }

        return null;
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return config('fatturapa.'.$key, $default);
    }
}
