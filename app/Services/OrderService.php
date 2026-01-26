<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ProductAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Crea un ordine dal carrello
     */
    public function createOrderFromCart(
        Cart $cart,
        Customer $customer,
        string $stripePaymentIntentId,
        ?string $stripePaymentMethod = null
    ): Order {
        return DB::transaction(function () use ($cart, $customer, $stripePaymentIntentId, $stripePaymentMethod) {
            // Genera numero ordine univoco
            $orderNumber = $this->generateOrderNumber($cart->company_id);

            // Crea l'ordine
            $order = Order::create([
                'customer_id' => $customer->id,
                'company_id' => $cart->company_id,
                'order_number' => $orderNumber,
                'amount' => $cart->total,
                'order_status' => OrderStatus::PENDING,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
                'stripe_payment_method' => $stripePaymentMethod,
            ]);

            // Crea i prodotti dell'ordine
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $cart->product_id,
                'product_availability_id' => $cart->product_availability_id,
                'booking_date' => $cart->date,
                'booking_time' => $cart->time,
                'price' => $cart->total,
                'quantity' => $cart->total_quantity,
                'total' => $cart->total,
                'quantity_full' => $cart->quantity_full,
                'quantity_reduced' => $cart->quantity_reduced,
                'quantity_free' => $cart->quantity_free,
                'price_full' => $cart->price_full,
                'price_reduced' => $cart->price_reduced,
                'price_free' => $cart->price_free,
            ]);

            // Decrementa la disponibilità
            $this->decrementAvailability($cart);

            return $order;
        });
    }

    /**
     * Completa un ordine (pagamento riuscito)
     */
    public function completeOrder(Order $order, ?string $paymentMethod = null): Order
    {
        $order->update([
            'order_status' => OrderStatus::PAID,
            'paid_at' => now(),
            'stripe_payment_method' => $paymentMethod ?? $order->stripe_payment_method,
            'payment_error' => null,
        ]);

        return $order->fresh();
    }

    /**
     * Marca un ordine come fallito
     */
    public function failOrder(Order $order, ?string $errorMessage = null): Order
    {
        $order->update([
            'order_status' => OrderStatus::FAILED,
            'payment_error' => $errorMessage,
        ]);

        // Ripristina la disponibilità se l'ordine fallisce
        $this->restoreAvailability($order);

        return $order->fresh();
    }

    /**
     * Trova un ordine tramite PaymentIntent ID
     */
    public function findByPaymentIntent(string $paymentIntentId): ?Order
    {
        return Order::where('stripe_payment_intent_id', $paymentIntentId)->first();
    }

    /**
     * Genera un numero ordine univoco
     */
    protected function generateOrderNumber(int $companyId): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$companyId}-{$date}-{$random}";
    }

    /**
     * Decrementa la disponibilità del prodotto
     */
    protected function decrementAvailability(Cart $cart): void
    {
        if ($cart->product_availability_id) {
            $availability = ProductAvailability::find($cart->product_availability_id);
            if ($availability) {
                $totalQuantity = $cart->quantity_full + $cart->quantity_reduced + $cart->quantity_free;
                $availability->decrement('availability', $totalQuantity);
            }
        }
    }

    /**
     * Ripristina la disponibilità del prodotto (in caso di ordine fallito)
     */
    protected function restoreAvailability(Order $order): void
    {
        foreach ($order->orderProducts as $orderProduct) {
            if ($orderProduct->product_availability_id) {
                $availability = ProductAvailability::find($orderProduct->product_availability_id);
                if ($availability) {
                    $totalQuantity = $orderProduct->quantity_full + $orderProduct->quantity_reduced + $orderProduct->quantity_free;
                    $availability->increment('availability', $totalQuantity);
                }
            }
        }
    }
}
