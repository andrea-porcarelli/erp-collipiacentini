<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Customer;
use App\Services\OrderService;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripePaymentService $stripeService,
        protected OrderService $orderService
    ) {}

    /**
     * Gestisce gli eventi webhook di Stripe
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response('Webhook error', 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
        }

        return response('OK', 200);
    }

    /**
     * Gestisce il pagamento riuscito
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $paymentIntentId = $paymentIntent->id;

        Log::info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntentId,
            'metadata' => $paymentIntent->metadata->toArray(),
        ]);

        // Verifica se l'ordine esiste già
        $existingOrder = $this->orderService->findByPaymentIntent($paymentIntentId);

        if ($existingOrder) {
            // Assicurati che l'ordine sia marcato come completato
            if ($existingOrder->paid_at === null) {
                $this->orderService->completeOrder($existingOrder, $paymentIntent->payment_method);
                Log::info('Order completed via webhook', [
                    'order_id' => $existingOrder->id,
                    'order_number' => $existingOrder->order_number,
                ]);
            }

            return;
        }

        // L'ordine non esiste, proviamo a crearlo
        $metadata = $paymentIntent->metadata;
        $sessionId = $metadata->session_id ?? null;
        $customerId = $metadata->customer_id ?? null;

        if (!$sessionId && !$customerId) {
            Log::warning('Cannot create order from webhook: missing session_id and customer_id', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        // Prova a recuperare il carrello
        $cart = $sessionId ? Cart::where('session_id', $sessionId)->first() : null;

        if (!$cart && $customerId) {
            $cart = Cart::where('customer_id', $customerId)->first();
        }

        if (!$cart) {
            Log::warning('Cannot create order from webhook: cart not found', [
                'payment_intent_id' => $paymentIntentId,
                'session_id' => $sessionId,
                'customer_id' => $customerId,
            ]);

            return;
        }

        if (!$cart->customer) {
            Log::warning('Cannot create order from webhook: customer not found', [
                'payment_intent_id' => $paymentIntentId,
                'cart_id' => $cart->id,
            ]);

            return;
        }

        try {
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

            Log::info('Order created via webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create order from webhook', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gestisce il pagamento fallito
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        $paymentIntentId = $paymentIntent->id;

        Log::info('Payment intent failed', [
            'payment_intent_id' => $paymentIntentId,
            'error' => $paymentIntent->last_payment_error?->message ?? 'Unknown error',
        ]);

        $existingOrder = $this->orderService->findByPaymentIntent($paymentIntentId);

        if ($existingOrder) {
            $errorMessage = $paymentIntent->last_payment_error?->message ?? 'Pagamento fallito';
            $this->orderService->failOrder($existingOrder, $errorMessage);

            Log::info('Order marked as failed via webhook', [
                'order_id' => $existingOrder->id,
                'order_number' => $existingOrder->order_number,
            ]);
        }
    }
}
