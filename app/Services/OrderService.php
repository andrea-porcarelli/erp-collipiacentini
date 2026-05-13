<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderProductItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(private ProductAvailabilityService $availabilityService) {}

    /**
     * Create an order from the cart, transferring cart items to order_product_items.
     */
    public function createOrderFromCart(
        Cart $cart,
        Customer $customer,
        ?string $stripePaymentIntentId = null,
        ?string $stripePaymentMethod = null
    ): Order {
        return DB::transaction(function () use ($cart, $customer, $stripePaymentIntentId, $stripePaymentMethod) {
            $cart->loadMissing('partner');
            $orderNumber = $this->generateOrderNumber($cart->partner->partner_code);

            $order = Order::create([
                'customer_id'               => $customer->id,
                'partner_id'                => $cart->partner_id,
                'order_number'              => $orderNumber,
                'amount'                    => $cart->total,
                'order_status'              => OrderStatus::PENDING,
                'stripe_payment_intent_id'  => $stripePaymentIntentId,
                'stripe_payment_method'     => $stripePaymentMethod,
            ]);

            $cart->loadMissing('items');
            $totalQuantity = $cart->items->sum('quantity');

            $orderProduct = OrderProduct::create([
                'order_id'                   => $order->id,
                'product_id'                 => $cart->product_id,
                'booking_date'               => $cart->date,
                'booking_time'               => $cart->time,
                'slot_type'                  => $cart->slot_type,
                'slot_id'                    => $cart->slot_id,
                'applied_price_variation_id' => $cart->applied_price_variation_id,
                'price'                      => $cart->total,
                'quantity'                   => $totalQuantity,
                'total'                      => $cart->total,
            ]);

            // Snapshot delle commissioni del partner sull'item: serve a congelare
            // le condizioni economiche al momento della prenotazione anche se in
            // futuro le commissioni del partner verranno modificate.
            $partner = $cart->partner;
            $commissionSnapshot = (float) $cart->total > 0 && $partner ? [
                'partner_commission_presale_low'        => $partner->commission_presale_low,
                'partner_commission_presale_high'       => $partner->commission_presale_high,
                'partner_commission_presale_threshold'  => $partner->commission_presale_threshold,
                'partner_commission_miticko_fixed'      => $partner->commission_miticko_fixed,
                'partner_commission_miticko_variable'   => $partner->commission_miticko_variable,
                'partner_commission_payment'            => $partner->commission_payment,
            ] : [];

            foreach ($cart->items as $item) {
                OrderProductItem::create([
                    'order_product_id'  => $orderProduct->id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                    'unit_price'         => $item->unit_price,
                    ...$commissionSnapshot,
                ]);
            }

            return $order;
        });
    }

    /**
     * Mark order as paid.
     */
    public function completeOrder(Order $order, ?string $paymentMethod = null): Order
    {
        $alreadyPaid = $order->order_status === OrderStatus::PAID;

        $effectivePaymentMethod = $paymentMethod ?? $order->stripe_payment_method;

        $cardBrand = null;
        $cardLast4 = null;
        if ($effectivePaymentMethod) {
            try {
                $pm = app(StripePaymentService::class)->retrievePaymentMethod($effectivePaymentMethod);
                $cardBrand = $pm->card->brand ?? null;
                $cardLast4 = $pm->card->last4 ?? null;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $order->update([
            'order_status'          => OrderStatus::PAID,
            'paid_at'               => now(),
            'stripe_payment_method' => $effectivePaymentMethod,
            'card_brand'            => $cardBrand ?? $order->card_brand,
            'card_last4'            => $cardLast4 ?? $order->card_last4,
            'payment_error'         => null,
        ]);

        $order = $order->fresh();

        // Invio email di conferma solo alla prima transizione verso PAID
        // (evita doppio invio quando webhook + confirm passano entrambi qui).
        if (!$alreadyPaid) {
            $this->sendConfirmationEmail($order);
        }

        return $order;
    }

    protected function sendConfirmationEmail(Order $order): void
    {
        try {
            $order->loadMissing(['customer', 'partner', 'orderProducts.product.category', 'orderProducts.items.variant']);
            if ($order->customer?->email) {
                Mail::to($order->customer->email)->send(new OrderConfirmationMail($order));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Mark order as failed.
     */
    public function failOrder(Order $order, ?string $errorMessage = null): Order
    {
        $order->update([
            'order_status'  => OrderStatus::FAILED,
            'payment_error' => $errorMessage,
        ]);

        return $order->fresh();
    }

    /**
     * Find order by Stripe PaymentIntent ID.
     */
    public function findByPaymentIntent(string $paymentIntentId): ?Order
    {
        return Order::where('stripe_payment_intent_id', $paymentIntentId)->first();
    }

    protected function generateOrderNumber(string $partnerCode): string
    {
        $prefix = 'ORD';
        $date   = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$partnerCode}-{$date}-{$random}";
    }

}
