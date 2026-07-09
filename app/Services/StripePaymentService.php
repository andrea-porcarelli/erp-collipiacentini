<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Crea un PaymentIntent su Stripe
     */
    public function createPaymentIntent(Cart $cart, ?Customer $customer = null): PaymentIntent
    {
        $amount = (int) round($cart->total * 100); // Stripe vuole i centesimi

        $params = [
            'amount' => $amount,
            'currency' => 'eur',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => [
                'cart_id' => $cart->id,
                'product_id' => $cart->product_id,
                'partner_id' => $cart->partner_id,
                'session_id' => $cart->session_id,
            ],
        ];

        if ($customer && $customer->email) {
            $params['receipt_email'] = $customer->email;
            $params['metadata']['customer_id'] = $customer->id;
        }

        return PaymentIntent::create($params);
    }

    /**
     * Recupera lo stato di un PaymentIntent
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Verifica la firma del webhook e costruisce l'evento
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        return Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
    }

    /**
     * Verifica se un PaymentIntent è completato con successo
     */
    public function isPaymentSuccessful(string $paymentIntentId): bool
    {
        $paymentIntent = $this->retrievePaymentIntent($paymentIntentId);
        return $paymentIntent->status === 'succeeded';
    }

    /**
     * Recupera un PaymentMethod (per leggere brand/last4 della carta)
     */
    public function retrievePaymentMethod(string $paymentMethodId): PaymentMethod
    {
        return PaymentMethod::retrieve($paymentMethodId);
    }

    /**
     * Crea una Stripe Checkout Session per un Order già esistente e ne restituisce
     * l'URL condivisibile (da inviare al cliente per il pagamento).
     *
     * La metadata `order_id` viene propagata anche al PaymentIntent, così il webhook
     * `payment_intent.succeeded` può marcare l'ordine come PAID.
     */
    public function createPaymentLinkForOrder(Order $order): CheckoutSession
    {
        $order->loadMissing(['orderProducts.product', 'orderProducts.items.variant', 'customer']);

        $lineItems = [];
        foreach ($order->orderProducts as $orderProduct) {
            $productLabel = $orderProduct->product?->label ?? 'Prodotto';
            foreach ($orderProduct->items as $item) {
                $variantLabel = $item->variant?->label ?? 'Variante';
                $lineItems[] = [
                    'quantity' => (int) $item->quantity,
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int) round(((float) $item->unit_price) * 100),
                        'product_data' => [
                            'name' => sprintf('%s — %s', $productLabel, $variantLabel),
                        ],
                    ],
                ];
            }
        }

        $successUrl = url("/order/success/{$order->order_number}");
        $cancelUrl = url("/order/success/{$order->order_number}");

        $params = [
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => (string) $order->order_number,
                ],
            ],
        ];

        if ($order->customer?->email) {
            $params['customer_email'] = $order->customer->email;
        }

        return CheckoutSession::create($params);
    }

    /**
     * Crea un rimborso (totale o parziale) su un PaymentIntent
     */
    public function refund(string $paymentIntentId, ?int $amount = null): Refund
    {
        $params = ['payment_intent' => $paymentIntentId];
        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        return Refund::create($params);
    }
}
