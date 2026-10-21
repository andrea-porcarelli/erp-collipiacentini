<?php

namespace App\Http\Controllers\Backoffice;

use App\Contracts\InvoiceProvider;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            // con qty, unit_price, vat_rate (dal ProductVariantPrice matchato per prezzo), subtotal.
            $orderItems = $order->orderProducts->flatMap(function ($op) {
                return $op->items->map(function ($it) {
                    $vat = $it->variant?->prices
                        ?->firstWhere(fn ($p) => abs((float) $p->price - (float) $it->unit_price) < 0.01)
                        ?->vat_rate;

                    return [
                        'label' => $it->variant?->label ?? '—',
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
            $partnerModel = $order->partner;
            $ticketsSubtotal = 0.0;
            $presaleTotal = 0.0;
            $presaleQuantity = 0;
            foreach ($order->orderProducts as $op) {
                foreach ($op->items as $it) {
                    $qty = max((int) $it->quantity, 1);
                    $ticketsSubtotal += (float) $it->unit_price * $qty;

                    $threshold = $it->partner_commission_presale_threshold ?? $partnerModel?->commission_presale_threshold;
                    if (is_null($threshold)) {
                        continue;
                    }
                    $low = (float) ($it->partner_commission_presale_low ?? $partnerModel?->commission_presale_low ?? 0);
                    $high = (float) ($it->partner_commission_presale_high ?? $partnerModel?->commission_presale_high ?? 0);
                    $unit = (float) $it->unit_price < (float) $threshold ? $low : $high;
                    $presaleTotal += $unit * $qty;
                    $presaleQuantity += $qty;
                }
            }
            $ticketsSubtotal = round($ticketsSubtotal, 2);
            $presaleTotal = round($presaleTotal, 2);
            $presaleUnit = $presaleQuantity > 0 ? round($presaleTotal / $presaleQuantity, 2) : 0.0;

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
                    'commission_miticko_rate' => (float) ($order->partner?->commission_miticko_variable ?? 0),
                    'commission_miticko_amount' => round($ticketsSubtotal * (float) ($order->partner?->commission_miticko_variable ?? 0) / 100, 2),
                    'presale_total' => $presaleTotal,
                    'presale_quantity' => $presaleQuantity,
                    'presale_unit' => $presaleUnit,
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
