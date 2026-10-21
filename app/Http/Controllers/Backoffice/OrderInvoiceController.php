<?php

namespace App\Http\Controllers\Backoffice;

use App\Contracts\InvoiceProvider;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceCalculatorService;
use App\Services\Invoicing\FatturaPaBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderInvoiceController extends Controller
{
    public function __construct(
        private InvoiceCalculatorService $calculator,
        private InvoiceProvider $provider,
    ) {}

    /**
     * Restituisce l'anteprima calcolata delle due fatture (partner + cliente).
     * L'admin può passare `flags[partner|customer][...]` per vedere il ricalcolo in tempo reale.
     */
    public function preview(Request $request, Order $order): JsonResponse
    {
        try {
            $overridePartner = $this->extractFlags($request, Invoice::RECIPIENT_PARTNER);
            $overrideCustomer = $this->extractFlags($request, Invoice::RECIPIENT_CUSTOMER);

            $partnerData = $this->calculator->computeForOrder($order, $overridePartner);
            $customerData = $overrideCustomer === null
                ? $partnerData
                : $this->calculator->computeForOrder($order, $overrideCustomer);

            $order->loadMissing('orderProducts.product', 'orderProducts.items.variant.prices');
            $products = $order->orderProducts
                ->map(fn ($op) => $op->product ? ['id' => $op->product->id, 'label' => $op->product->label] : null)
                ->filter()->unique('id')->values()->all();
            $productLabels = array_column($products, 'label');
            $variants = $order->orderProducts
                ->flatMap(fn ($op) => $op->items->map(fn ($it) => $it->variant?->label))
                ->filter()->values()->all();

            // Dettaglio righe ordine: una riga per OrderProductItem (variante acquistata)
            // con qty, unit_price, vat_rate, subtotal.
            // `label` = nome variante (usato nello Step 1), `product_label` = nome prodotto (usato nello Step 2).
            // vat_rate: usa lo snapshot su OPI; fallback per ordini legacy a match PVP su prezzo puro
            // (unit_price − prevendita stimata, perché unit_price include già la prevendita).
            $partnerModel = $order->partner;
            $orderItems = $order->orderProducts->flatMap(function ($op) use ($partnerModel) {
                $productLabel = $op->product?->label;

                return $op->items->map(function ($it) use ($productLabel, $partnerModel) {
                    $vat = $it->vat_rate;
                    if (is_null($vat)) {
                        $thresholdHigh = $it->partner_commission_presale_threshold_high ?? $partnerModel?->commission_presale_threshold_high;
                        $thresholdLow = $it->partner_commission_presale_threshold_low ?? $partnerModel?->commission_presale_threshold_low;
                        $high = (float) ($it->partner_commission_presale_high ?? $partnerModel?->commission_presale_high ?? 0);
                        $low = (float) ($it->partner_commission_presale_low ?? $partnerModel?->commission_presale_low ?? 0);
                        $unit = (float) $it->unit_price;
                        $presaleUnit = 0.0;
                        if (! is_null($thresholdHigh) && ($unit - $high) > (float) $thresholdHigh) {
                            $presaleUnit = $high;
                        } elseif (! is_null($thresholdLow) && ($unit - $low) < (float) $thresholdLow) {
                            $presaleUnit = $low;
                        }
                        $purePrice = round($unit - $presaleUnit, 2);
                        $vat = $it->variant?->prices
                            ?->firstWhere(fn ($p) => abs((float) $p->price - $purePrice) < 0.01)
                            ?->vat_rate;
                    }

                    return [
                        'label' => $it->variant?->label ?? '—',
                        'product_label' => $productLabel ?? '—',
                        'quantity' => (int) $it->quantity,
                        'unit_price' => (float) $it->unit_price,
                        'vat_rate' => is_null($vat) ? null : (float) $vat,
                        'subtotal' => round((float) $it->unit_price * (int) $it->quantity, 2),
                    ];
                });
            })->values()->all();

            // Aggregati sull'intero ordine (indipendenti dai flag `bill_*` dei singoli item):
            //  - subtotale biglietti "puro" (base per le commissioni %)
            //  - prevendita: add-on partner — low/high in base alla soglia sul unit_price del biglietto.
            // Servono per le righe DI CUI nel riepilogo ordine.
            $ticketsSubtotal = 0.0;
            $presaleTotal = 0.0;
            $presaleQuantity = 0;
            foreach ($order->orderProducts as $op) {
                foreach ($op->items as $it) {
                    $qty = max((int) $it->quantity, 1);
                    $ticketsSubtotal += (float) $it->unit_price * $qty;

                    $thresholdHigh = $it->partner_commission_presale_threshold_high ?? $partnerModel?->commission_presale_threshold_high;
                    $thresholdLow = $it->partner_commission_presale_threshold_low ?? $partnerModel?->commission_presale_threshold_low;
                    $low = (float) ($it->partner_commission_presale_low ?? $partnerModel?->commission_presale_low ?? 0);
                    $high = (float) ($it->partner_commission_presale_high ?? $partnerModel?->commission_presale_high ?? 0);
                    $unitPrice = (float) $it->unit_price;
                    $unit = 0.0;
                    if (! is_null($thresholdHigh) && $unitPrice > (float) $thresholdHigh) {
                        $unit = $high;
                    } elseif (! is_null($thresholdLow) && $unitPrice < (float) $thresholdLow) {
                        $unit = $low;
                    }
                    if ($unit <= 0) {
                        continue;
                    }
                    $presaleTotal += $unit * $qty;
                    $presaleQuantity += $qty;
                }
            }
            $ticketsSubtotal = round($ticketsSubtotal, 2);
            $presaleTotal = round($presaleTotal, 2);
            $presaleUnit = $presaleQuantity > 0 ? round($presaleTotal / $presaleQuantity, 2) : 0.0;

            // Aliquote IVA delle commissioni: snapshot su OPI al momento dell'ordine, con
            // fallback alla configurazione corrente del partner (per ordini pre-snapshot).
            // Prevendita e Miticko hanno IVA separata per riga: nel modal di anteprima mostriamo
            // una sola IVA per tipologia, quindi usiamo quella della fascia "high" / variabile come default.
            $firstItem = $order->orderProducts->flatMap(fn ($op) => $op->items)->first();
            $presaleVatSnapshot = $firstItem?->partner_commission_presale_high_vat
                ?? $firstItem?->partner_commission_presale_low_vat
                ?? $order->partner?->commission_presale_high_vat
                ?? $order->partner?->commission_presale_low_vat;
            $mitickoVatSnapshot = $firstItem?->partner_commission_miticko_variable_vat
                ?? $order->partner?->commission_miticko_variable_vat;
            $paymentVatSnapshot = $firstItem?->partner_commission_payment_vat ?? $order->partner?->commission_payment_vat;

            return response()->json([
                'partner' => $partnerData[Invoice::RECIPIENT_PARTNER],
                'customer' => $customerData[Invoice::RECIPIENT_CUSTOMER],
                'order_meta' => [
                    'product_labels' => $productLabels,
                    'products' => $products,
                    'variants' => $variants,
                    'items' => $orderItems,
                    'order_total' => (float) $order->amount,
                    // Rate commissioni e importi "DI CUI": la % viene sempre applicata sul subtotale
                    // biglietti "puro" (prima di sommare quota fissa Miticko e prevendita), coerente
                    // con la logica di InvoiceCalculatorService::computeAmountsForItem().
                    'commission_payment_rate' => (float) ($order->partner?->commission_payment ?? 0),
                    'commission_payment_amount' => round($ticketsSubtotal * (float) ($order->partner?->commission_payment ?? 0) / 100, 2),
                    'commission_payment_vat' => $this->normalizeVat($paymentVatSnapshot),
                    'commission_miticko_rate' => (float) ($order->partner?->commission_miticko_variable ?? 0),
                    'commission_miticko_amount' => round($ticketsSubtotal * (float) ($order->partner?->commission_miticko_variable ?? 0) / 100, 2),
                    'commission_miticko_vat' => $this->normalizeVat($mitickoVatSnapshot),
                    'presale_total' => $presaleTotal,
                    'presale_quantity' => $presaleQuantity,
                    'presale_unit' => $presaleUnit,
                    'presale_vat' => $this->normalizeVat($presaleVatSnapshot),
                    'payment_method' => $order->card_brand
                        ? sprintf('%s **** %s', ucfirst($order->card_brand), $order->card_last4 ?: '')
                        : ($order->stripe_payment_method ?: '—'),
                ],
                'provider' => [
                    'name' => $this->provider->name(),
                    'configured' => $this->provider->isConfigured(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    /**
     * Emette una o entrambe le fatture. Il payload contiene:
     *   {
     *     "recipients": ["partner", "customer"],
     *     "flags": { "partner": {...}, "customer": {...} }
     *   }
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        try {
            $request->validate([
                'recipients' => ['required', 'array', 'min:1'],
                'recipients.*' => ['in:partner,customer'],
                'description' => ['nullable', 'string', 'max:400'],
            ]);

            $recipients = array_values(array_unique($request->input('recipients', [])));
            $description = (string) $request->input('description', '');
            $issued = [];

            DB::transaction(function () use ($order, $request, $recipients, $description, &$issued) {
                foreach ($recipients as $recipient) {
                    $flags = $this->extractFlags($request, $recipient);
                    $computed = $this->calculator->computeForOrder($order, $flags);
                    $bucket = $computed[$recipient];

                    if (empty($bucket['lines'])) {
                        continue;
                    }

                    // Default: cliente privato → SDI convenzionale "0000000".
                    $sdiCode = $bucket['recipient']['sdi_code'] ?: ($recipient === Invoice::RECIPIENT_CUSTOMER ? '0000000' : null);

                    $invoice = Invoice::create([
                        'order_id' => $order->id,
                        'recipient_type' => $recipient,
                        'partner_id' => $recipient === Invoice::RECIPIENT_PARTNER ? $order->partner_id : null,
                        'recipient_name' => $bucket['recipient']['name'],
                        'recipient_vat_number' => $bucket['recipient']['vat_number'],
                        'recipient_tax_code' => $bucket['recipient']['tax_code'],
                        'recipient_address' => $bucket['recipient']['address'],
                        'recipient_postal_code' => $bucket['recipient']['postal_code'],
                        'recipient_city' => $bucket['recipient']['city'],
                        'recipient_province' => $bucket['recipient']['province'],
                        'recipient_country' => $bucket['recipient']['country'],
                        'recipient_email' => $bucket['recipient']['email'],
                        'recipient_pec' => $bucket['recipient']['pec'],
                        'recipient_sdi_code' => $sdiCode,
                        'description' => $description ?: null,
                        'lines' => $bucket['lines'],
                        'total' => $bucket['total'],
                        'currency' => 'EUR',
                        'status' => Invoice::STATUS_QUEUED,
                        'emitted_at' => now(),
                        'emitted_by_user_id' => Auth::id(),
                    ]);

                    $issued[] = $this->provider->send($invoice)->fresh();
                }
            });

            if (empty($issued)) {
                return response()->json([
                    'success' => false,
                    'error_message' => 'Nessuna voce da fatturare per i destinatari selezionati.',
                ], 422);
            }

            $hasError = collect($issued)->contains(fn ($i) => in_array($i->status, [Invoice::STATUS_ERROR, Invoice::STATUS_REJECTED], true));

            return response()->json([
                'success' => ! $hasError,
                'error_message' => $hasError ? collect($issued)->firstWhere('status', Invoice::STATUS_ERROR)?->provider_error : null,
                'invoices' => $issued,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    /**
     * Scarica l'XML FatturaPA (SdI compliance) per una singola Invoice associata all'ordine.
     * Costruito on-the-fly dai dati della Invoice via FatturaPaBuilder, quindi disponibile
     * anche per fatture in stato DRAFT (quando il provider non è configurato).
     */
    public function downloadXml(Order $order, Invoice $invoice, FatturaPaBuilder $builder): Response
    {
        abort_unless($invoice->order_id === $order->id, 404);

        $xml = $builder->build($invoice);
        $filename = $builder->filename($invoice);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Ritorna lo storico di tutti i tentativi di invio verso il provider di fatturazione
     * per ciascuna fattura associata all'ordine (una Order può avere Invoice partner +
     * Invoice customer, ciascuna con N tentativi).
     */
    public function logs(Order $order): JsonResponse
    {
        try {
            $order->loadMissing(['invoices.logs.user', 'invoices.emittedBy']);

            $invoices = $order->invoices->map(function (Invoice $invoice) {
                return [
                    'id' => $invoice->id,
                    'recipient_type' => $invoice->recipient_type,
                    'recipient_name' => $invoice->recipient_name,
                    'number' => $invoice->number,
                    'status' => $invoice->status,
                    'provider' => $invoice->provider,
                    'provider_id' => $invoice->provider_id,
                    'provider_error' => $invoice->provider_error,
                    'emitted_at' => $invoice->emitted_at?->format('d/m/Y H:i:s'),
                    'emitted_by' => $invoice->emittedBy?->email,
                    // Dati fattura "pre-provider" utili per download anche quando è DRAFT
                    // (non ha mai raggiunto FIC → nessun payload HTTP salvato).
                    'description' => $invoice->description,
                    'total' => (float) $invoice->total,
                    'currency' => $invoice->currency,
                    'lines' => $invoice->lines,
                    'logs' => $invoice->logs->map(fn ($l) => [
                        'id' => $l->id,
                        'attempt' => $l->attempt,
                        'provider' => $l->provider,
                        'request_payload' => $l->request_payload,
                        'response_status' => $l->response_status,
                        'response_body' => $l->response_body,
                        'error_message' => $l->error_message,
                        'user' => $l->user?->email,
                        'created_at' => $l->created_at?->format('d/m/Y H:i:s'),
                    ])->values()->all(),
                ];
            })->values()->all();

            return response()->json([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, request());
        }
    }

    /**
     * Normalizza l'aliquota IVA configurata sul partner. Il valore può essere:
     *  - un'aliquota numerica ("0", "4", "10", "22", …) → ritornata come float;
     *  - un codice natura di fattura elettronica ("N1", "N6.9", …) → ritornato as-is;
     *  - null / vuoto → ritorna null.
     * "0" rappresenta "Esente".
     */
    private function normalizeVat(mixed $value): float|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $s = (string) $value;
        if (preg_match('/^N/i', $s)) {
            return strtoupper($s);
        }

        return (float) $s;
    }

    /**
     * @return array<string,bool>|null
     */
    private function extractFlags(Request $request, string $recipient): ?array
    {
        $flags = $request->input("flags.{$recipient}");
        if (! is_array($flags)) {
            return null;
        }
        $normalized = [];
        foreach ($flags as $key => $value) {
            $normalized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $normalized;
    }
}
