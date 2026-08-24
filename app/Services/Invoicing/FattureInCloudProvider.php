<?php

namespace App\Services\Invoicing;

use App\Contracts\InvoiceProvider;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter Fatture in Cloud (API v2).
 * Docs: https://developers.fattureincloud.it/reference
 *
 * Env richieste (config/services.php → fatture_in_cloud):
 *   FIC_ACCESS_TOKEN — token OAuth long-lived per la company
 *   FIC_COMPANY_ID   — id della company Miticko su Fatture in Cloud
 *   FIC_API_BASE     — default https://api-v2.fattureincloud.it (override per sandbox)
 */
class FattureInCloudProvider implements InvoiceProvider
{
    public function __construct(
        private ?string $accessToken,
        private ?int $companyId,
        private string $apiBase,
    ) {}

    public function name(): string
    {
        return 'fatture_in_cloud';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->accessToken) && ! empty($this->companyId);
    }

    public function send(Invoice $invoice): Invoice
    {
        if (! $this->isConfigured()) {
            $invoice->update([
                'status' => Invoice::STATUS_ERROR,
                'provider' => $this->name(),
                'provider_error' => 'FIC access token o company id mancanti.',
            ]);

            return $invoice;
        }

        try {
            $payload = $this->buildPayload($invoice);

            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post("{$this->apiBase}/c/{$this->companyId}/issued_documents", $payload);

            if ($response->failed()) {
                $invoice->update([
                    'status' => Invoice::STATUS_ERROR,
                    'provider' => $this->name(),
                    'provider_error' => sprintf('FIC HTTP %d: %s', $response->status(), $response->body()),
                    'provider_payload' => json_encode($payload),
                ]);

                return $invoice;
            }

            $data = $response->json('data') ?? [];

            $invoice->update([
                'status' => Invoice::STATUS_SENT,
                'provider' => $this->name(),
                'provider_id' => (string) ($data['id'] ?? ''),
                'provider_uuid' => (string) ($data['ei_status'] ?? ''),
                'provider_error' => null,
                'provider_payload' => json_encode($payload),
                'number' => $data['number'] ?? $invoice->number,
            ]);
        } catch (\Throwable $e) {
            Log::error('FattureInCloud send failed', ['invoice_id' => $invoice->id, 'exception' => $e]);
            $invoice->update([
                'status' => Invoice::STATUS_ERROR,
                'provider' => $this->name(),
                'provider_error' => $e->getMessage(),
            ]);
        }

        return $invoice;
    }

    private function buildPayload(Invoice $invoice): array
    {
        $lines = collect($invoice->lines ?? [])
            ->map(fn ($line) => [
                'name' => $line['label'] ?? 'Voce',
                'qty' => (float) ($line['quantity'] ?? 1),
                'net_price' => (float) ($line['unit_amount'] ?? 0),
                'category' => $line['code'] ?? null,
            ])
            ->values()
            ->toArray();

        return [
            'data' => [
                'type' => 'invoice',
                'entity' => [
                    'name' => $invoice->recipient_name,
                    'vat_number' => $invoice->recipient_vat_number,
                    'tax_code' => $invoice->recipient_tax_code,
                    'address_street' => $invoice->recipient_address,
                    'address_postal_code' => $invoice->recipient_postal_code,
                    'address_city' => $invoice->recipient_city,
                    'address_province' => $invoice->recipient_province,
                    'country_iso' => $invoice->recipient_country ?? 'IT',
                    'email' => $invoice->recipient_email,
                    'certified_email' => $invoice->recipient_pec,
                    'ei_code' => $invoice->recipient_sdi_code,
                ],
                'currency' => ['id' => $invoice->currency ?? 'EUR'],
                'items_list' => $lines,
                'e_invoice' => true,
            ],
        ];
    }
}
