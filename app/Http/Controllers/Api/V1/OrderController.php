<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PartnerConsent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use App\Services\ProductAvailabilityService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private StripePaymentService $stripe,
        private OrderService $orderService,
        private ProductAvailabilityService $availability,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $product = $request->get('widget_product');
        if (! $product instanceof Product) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'string'],
            'slot_type' => ['required', 'in:weekly,special'],
            'slot_id' => ['nullable', 'integer'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.variant_id' => ['required', 'integer'],
            'variants.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $slot = $this->availability->getSlot($product, $data['date'], $data['time']);
        if (! $slot) {
            return response()->json(['error' => 'slot_unavailable', 'message' => 'Slot non disponibile'], 422);
        }

        $requestedQty = collect($data['variants'])->sum('quantity');
        if (! is_null($slot['availability']) && $slot['availability'] < $requestedQty) {
            return response()->json([
                'error' => 'insufficient_capacity',
                'message' => 'Posti insufficienti per la quantità richiesta',
                'available' => $slot['availability'],
            ], 422);
        }

        if ($product->max_tickets_per_session && $requestedQty > $product->max_tickets_per_session) {
            return response()->json([
                'error' => 'max_tickets_exceeded',
                'message' => 'Superato il massimo di biglietti per prenotazione: '.$product->max_tickets_per_session,
            ], 422);
        }

        $variantIds = collect($data['variants'])->pluck('variant_id')->unique();
        $variants = ProductVariant::whereIn('id', $variantIds)
            ->where('product_id', $product->id)
            ->get()->keyBy('id');

        foreach ($data['variants'] as $v) {
            if (! $variants->has($v['variant_id'])) {
                return response()->json(['error' => 'invalid_variant', 'variant_id' => $v['variant_id']], 422);
            }
        }

        $priceVariation = $this->availability->getApplicablePriceVariation($product, $data['date']);

        $total = 0.0;
        $itemsSpec = [];
        foreach ($data['variants'] as $v) {
            $variant = $variants[$v['variant_id']];
            $basePrice = (float) $variant->full_price;
            $unitPrice = $priceVariation
                ? $this->availability->applyPriceVariation($basePrice, $priceVariation)
                : $basePrice;
            $total += $unitPrice * $v['quantity'];
            $itemsSpec[] = [
                'product_variant_id' => $variant->id,
                'quantity' => $v['quantity'],
                'unit_price' => round($unitPrice, 2),
            ];
        }

        $cart = DB::transaction(function () use ($product, $data, $slot, $total, $itemsSpec, $priceVariation) {
            $cart = Cart::create([
                'session_id' => (string) Str::uuid(),
                'partner_id' => $product->partner_id,
                'product_id' => $product->id,
                'date' => $data['date'],
                'time' => $data['time'],
                'slot_type' => $slot['slot_type'],
                'slot_id' => $slot['slot_id'],
                'applied_price_variation_id' => $priceVariation?->id,
                'total' => round($total, 2),
            ]);

            foreach ($itemsSpec as $item) {
                CartItem::create(array_merge(['cart_id' => $cart->id], $item));
            }

            return $cart;
        });

        return response()->json($this->serializeCart($cart->fresh('items')), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $cart = $this->findCartForWidget($request, $id);
        if (! $cart) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json($this->serializeCart($cart));
    }

    public function customer(Request $request, int $id): JsonResponse
    {
        $cart = $this->findCartForWidget($request, $id);
        if (! $cart) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'prefix_phone' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'fiscal_code' => ['nullable', 'string', 'max:30'],
            'privacy_accepted' => ['required', 'accepted'],
            'newsletter' => ['nullable', 'boolean'],
            'consents_payload' => ['nullable', 'array'],
        ]);

        // Verifica consensi richiesti configurati sul partner.
        $requiredConsents = PartnerConsent::where('partner_id', $cart->partner_id)
            ->current()
            ->where('is_active', true)
            ->where('is_required', true)
            ->pluck('code')
            ->all();

        $payload = $data['consents_payload'] ?? [];
        foreach ($requiredConsents as $code) {
            if (empty($payload[$code])) {
                return response()->json([
                    'error' => 'missing_required_consent',
                    'message' => "Consenso obbligatorio mancante: $code",
                    'code' => $code,
                ], 422);
            }
        }

        $cart->fill($data)->save();

        return response()->json($this->serializeCart($cart));
    }

    public function paymentIntent(Request $request, int $id): JsonResponse
    {
        $cart = $this->findCartForWidget($request, $id);
        if (! $cart || ! $cart->email) {
            return response()->json(['error' => 'missing_customer', 'message' => 'Compila prima i dati cliente'], 400);
        }

        try {
            $intent = $this->stripe->createPaymentIntent($cart);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'publishable_key' => config('services.stripe.key'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'stripe_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $cart = $this->findCartForWidget($request, $id);
        if (! $cart) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $data = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        try {
            $intent = $this->stripe->retrievePaymentIntent($data['payment_intent_id']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'stripe_error', 'message' => $e->getMessage()], 500);
        }

        if ($intent->status !== 'succeeded') {
            return response()->json(['error' => 'not_paid', 'status' => $intent->status], 400);
        }

        // L'ordine viene creato in PENDING; la transizione a PAID è autorità esclusiva
        // del webhook payment_intent.succeeded (necessario per gestire correttamente
        // i metodi asincroni come SEPA/Klarna dove il confirm può essere succeeded ma
        // il pagamento poi fallire).
        $order = $this->orderService->findByPaymentIntent($intent->id)
            ?? $this->orderService->createOrderFromCart($cart, $intent->id, $intent->payment_method ?? null);

        $cart->delete();

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->order_status,
            'success_url' => route('widget.booking', ['productCode' => $order->order_number], false),
        ]);
    }

    private function findCartForWidget(Request $request, int $id): ?Cart
    {
        $cart = Cart::find($id);
        if (! $cart) {
            return null;
        }

        // Il token widget è vincolato a un product_id: l'ordine deve matchare.
        $product = $request->get('widget_product');
        if ($product instanceof Product && $cart->product_id !== $product->id) {
            return null;
        }

        return $cart;
    }

    private function serializeCart(Cart $cart): array
    {
        $cart->loadMissing('items.variant');

        return [
            'id' => $cart->id,
            'product_id' => 'ERP-PROD-'.$cart->product_id,
            'partner_id' => $cart->partner_id,
            'date' => $cart->date?->format('Y-m-d'),
            'time' => $cart->time,
            'slot_type' => $cart->slot_type,
            'slot_id' => $cart->slot_id,
            'total' => (float) $cart->total,
            'items' => $cart->items->map(fn ($i) => [
                'variant_id' => $i->product_variant_id,
                'label' => $i->variant?->label,
                'quantity' => $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'subtotal' => (float) $i->subtotal,
            ])->all(),
            'customer' => $cart->email ? [
                'name' => $cart->name,
                'surname' => $cart->surname,
                'email' => $cart->email,
                'phone' => $cart->phone,
                'fiscal_code' => $cart->fiscal_code,
            ] : null,
        ];
    }
}
