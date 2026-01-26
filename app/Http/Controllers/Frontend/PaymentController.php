<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected StripePaymentService $stripeService,
        protected OrderService $orderService
    ) {}

    /**
     * Crea un PaymentIntent per il carrello corrente
     */
    public function createIntent(Request $request): JsonResponse
    {
        $cart = Cart::getBySession();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'error' => 'Carrello non trovato',
            ], 404);
        }

        if (!$cart->customer_id) {
            return response()->json([
                'success' => false,
                'error' => 'Dati cliente mancanti',
            ], 400);
        }

        try {
            $customer = $cart->customer;
            $paymentIntent = $this->stripeService->createPaymentIntent($cart, $customer);

            return response()->json([
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Errore nella creazione del pagamento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conferma il pagamento e crea l'ordine
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $cart = Cart::getBySession();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'error' => 'Carrello non trovato',
            ], 404);
        }

        try {
            $paymentIntentId = $request->input('payment_intent_id');

            // Verifica lo stato del pagamento su Stripe
            $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'error' => 'Il pagamento non è stato completato',
                    'status' => $paymentIntent->status,
                ], 400);
            }

            // Verifica se l'ordine è già stato creato (es. dal webhook)
            $existingOrder = $this->orderService->findByPaymentIntent($paymentIntentId);

            if ($existingOrder) {
                // Ordine già esistente, elimina il carrello e ritorna successo
                $cart->delete();

                return response()->json([
                    'success' => true,
                    'order_number' => $existingOrder->order_number,
                    'redirect_url' => route('order.success', $existingOrder->order_number),
                ]);
            }

            // Crea l'ordine
            $order = $this->orderService->createOrderFromCart(
                $cart,
                $cart->customer,
                $paymentIntentId,
                $paymentIntent->payment_method
            );

            // Completa l'ordine
            $this->orderService->completeOrder($order, $paymentIntent->payment_method);

            // Elimina il carrello
            $cart->delete();

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'redirect_url' => route('order.success', $order->order_number),
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Errore nella conferma del pagamento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pagina di successo ordine
     */
    public function success_payment(string $orderNumber): View
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['customer', 'orderProducts.product', 'company'])
            ->firstOrFail();

        $company = $order->company;

        return view('whitelabel.order-success', compact('order', 'company'));
    }
}
