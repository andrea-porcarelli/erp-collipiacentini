<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Partner;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private StripePaymentService $stripe,
        private OrderService $orderService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'variants' => ['nullable', 'array'],
        ]);

        $productId = $this->extractNumericId($data['product_id']);
        $product = Product::with('partner')->find($productId);

        if (! $product) {
            return response()->json(['error' => 'not_found', 'message' => 'Prodotto non trovato'], 404);
        }

        $partner = $this->tenantPartner($request, $product);
        if (! $partner) {
            return response()->json(['error' => 'forbidden', 'message' => 'Prodotto non associato al partner richiedente'], 403);
        }

        // TODO: validate availability + slot and compute total using ProductAvailabilityService.
        $cart = Cart::create([
            'session_id' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'date' => $data['date'],
            'time' => $data['time'] ?? null,
            'total' => 0,
        ]);

        return response()->json($this->serializeCart($cart), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $cart = Cart::find($id);
        if (! $cart) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json($this->serializeCart($cart));
    }

    public function customer(Request $request, int $id): JsonResponse
    {
        $cart = Cart::find($id);
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
            'privacy_accepted' => ['required', 'boolean'],
            'newsletter' => ['nullable', 'boolean'],
            'consents_payload' => ['nullable', 'array'],
        ]);

        $cart->fill($data)->save();

        return response()->json($this->serializeCart($cart));
    }

    public function paymentIntent(Request $request, int $id): JsonResponse
    {
        $cart = Cart::find($id);
        if (! $cart || ! $cart->email) {
            return response()->json(['error' => 'missing_customer', 'message' => 'Compilare prima i dati cliente'], 400);
        }

        try {
            $intent = $this->stripe->createPaymentIntent($cart);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'stripe_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $cart = Cart::find($id);
        if (! $cart) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $data = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        $intent = $this->stripe->retrievePaymentIntent($data['payment_intent_id']);
        if ($intent->status !== 'succeeded') {
            return response()->json(['error' => 'not_paid', 'status' => $intent->status], 400);
        }

        $order = $this->orderService->findByPaymentIntent($intent->id)
            ?? $this->orderService->createOrderFromCart($cart, $intent->id);

        $cart->delete();

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->order_status,
        ]);
    }

    private function serializeCart(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'product_id' => 'ERP-PROD-'.$cart->product_id,
            'partner_id' => $cart->partner_id,
            'date' => $cart->date?->format('Y-m-d'),
            'time' => $cart->time,
            'total' => (float) $cart->total,
            'customer' => $cart->email ? [
                'name' => $cart->name,
                'surname' => $cart->surname,
                'email' => $cart->email,
            ] : null,
        ];
    }

    private function tenantPartner(Request $request, Product $product): ?Partner
    {
        $partner = $request->get('partner');
        if ($partner instanceof Partner) {
            return $product->partner_id === $partner->id ? $partner : null;
        }

        // Legacy fallback: Company-level token. In pratica il widget nasce come Partner-only.
        return $product->partner;
    }

    private function extractNumericId(string $id): int
    {
        if (str_starts_with($id, 'ERP-PROD-')) {
            return (int) substr($id, 9);
        }

        return (int) $id;
    }
}
