<?php

namespace App\Services\Invoicing;

use App\Contracts\InvoiceProvider;
use App\Models\Invoice;
use App\Models\InvoiceLog;
use Illuminate\Support\Facades\Auth;
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
            $this->writeLog($invoice, null, null, null, 'FIC access token o company id mancanti.');

            return $invoice;
        }

        $payload = null;
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
                $this->writeLog($invoice, $payload, $response->status(), $response->body(), null);

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
            $this->writeLog($invoice, $payload, $response->status(), $response->body(), null);
        } catch (\Throwable $e) {
            Log::error('FattureInCloud send failed', ['invoice_id' => $invoice->id, 'exception' => $e]);
            $invoice->update([
                'status' => Invoice::STATUS_ERROR,
                'provider' => $this->name(),
                'provider_error' => $e->getMessage(),
            ]);
            $this->writeLog($invoice, $payload, null, null, $e->getMessage());
        }

        return $invoice;
    }

    /**
     * Persiste un InvoiceLog con snapshot immutabile del tentativo appena fatto.
     * Il campo `attempt` viene incrementato in base al numero di log già presenti per
     * la invoice (coerenza garantita dalla transazione del chiamante).
     */
    private function writeLog(Invoice $invoice, ?array $payload, ?int $status, ?string $body, ?string $error): void
    {
        $attempt = InvoiceLog::where('invoice_id', $invoice->id)->max('attempt') ?? 0;

        InvoiceLog::create([
            'invoice_id' => $invoice->id,
            'attempt' => $attempt + 1,
            'provider' => $this->name(),
            'request_payload' => $payload,
            'response_status' => $status,
            'response_body' => $this->decodeResponseBody($body),
            'error_message' => $error,
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    /**
     * La risposta può essere JSON (shape FIC) oppure testo libero su errori generici:
     * provo a decodificare come JSON e, se fallisce, incapsulo il testo in `['raw' => …]`
     * così il cast `array` sul model non va in errore.
     */
    private function decodeResponseBody(?string $body): ?array
    {
        if ($body === null || $body === '') {
            return null;
        }
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return ['raw' => $body];
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
                'description' => (string) ($invoice->description ?? ''),
                'notes' => (string) ($invoice->description ?? ''),
                'e_invoice' => true,
            ],
        ];
    }
}
