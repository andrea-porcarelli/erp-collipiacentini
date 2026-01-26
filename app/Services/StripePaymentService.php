<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Customer;
use Stripe\PaymentIntent;
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
                'company_id' => $cart->company_id,
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
}
