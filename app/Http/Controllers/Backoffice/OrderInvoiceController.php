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

            $order->loadMissing('orderProducts.product', 'orderProducts.items.variant');
            $productLabels = $order->orderProducts
                ->map(fn ($op) => $op->product?->label)
                ->filter()->unique()->values()->all();
            $variants = $order->orderProducts
                ->flatMap(fn ($op) => $op->items->map(fn ($it) => $it->variant?->label))
                ->filter()->values()->all();

            return response()->json([
                'partner' => $partnerData[Invoice::RECIPIENT_PARTNER],
                'customer' => $customerData[Invoice::RECIPIENT_CUSTOMER],
                'order_meta' => [
                    'product_labels' => $productLabels,
                    'variants' => $variants,
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
