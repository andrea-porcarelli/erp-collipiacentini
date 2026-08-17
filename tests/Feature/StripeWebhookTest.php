<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Http\Controllers\Frontend\StripeWebhookController;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Costruisce un fake PaymentIntent minimale compatibile con il consumo
     * di StripeWebhookController: espone id, metadata (con toArray), payment_method.
     */
    protected function fakePaymentIntent(string $id, array $metadata, string $paymentMethod = 'pm_fake_1'): object
    {
        return new class($id, $metadata, $paymentMethod)
        {
            public string $id;

            public object $metadata;

            public string $payment_method;

            public function __construct(string $id, array $metadata, string $paymentMethod)
            {
                $this->id = $id;
                $this->payment_method = $paymentMethod;
                $this->metadata = new class($metadata)
                {
                    private array $data;

                    public function __construct(array $data)
                    {
                        $this->data = $data;
                    }

                    public function __get(string $key)
                    {
                        return $this->data[$key] ?? null;
                    }

                    public function __isset(string $key): bool
                    {
                        return isset($this->data[$key]);
                    }

                    public function toArray(): array
                    {
                        return $this->data;
                    }
                };
            }
        };
    }

    /**
     * Invoca il metodo protected handlePaymentIntentSucceeded senza passare
     * dal middleware HTTP e dalla firma Stripe.
     */
    protected function invokeWebhookHandler(object $paymentIntent): void
    {
        $controller = app(StripeWebhookController::class);
        $method = new ReflectionMethod($controller, 'handlePaymentIntentSucceeded');
        $method->setAccessible(true);
        $method->invoke($controller, $paymentIntent);
    }

    public function test_it_completes_manual_order_on_payment_intent_succeeded_with_order_id_metadata(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();
        $partner = $baseline['partner'];

        $order = Order::create([
            'partner_id' => $partner->id,
            'order_number' => 'ORD-TT-20260812-MAN1',
            'amount' => 10.00,
            'order_status' => OrderStatus::PENDING,
            'name' => 'Manual',
            'surname' => 'Order',
            'email' => 'manual@example.com',
            'privacy_accepted' => true,
        ]);

        $intent = $this->fakePaymentIntent('pi_manual_1', ['order_id' => (string) $order->id]);
        $this->invokeWebhookHandler($intent);

        $order->refresh();
        $this->assertSame(OrderStatus::PAID, $order->order_status);
        $this->assertSame('pi_manual_1', $order->stripe_payment_intent_id);
        $this->assertNotNull($order->paid_at);
        Mail::assertSent(OrderConfirmationMail::class);
    }

    public function test_it_creates_order_from_cart_on_payment_intent_succeeded_with_session_id_metadata(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();
        $partner = $baseline['partner'];
        $product = $baseline['product'];
        $variant = $baseline['variant'];

        $sessionId = Str::random(40);

        $cart = Cart::create([
            'session_id' => $sessionId,
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'date' => $baseline['booking_date'],
            'time' => $baseline['booking_time'],
            'slot_type' => 'weekly',
            'slot_id' => $product->availabilities->first()->id,
            'total' => 10.00,
            'name' => 'Cart',
            'surname' => 'Buyer',
            'email' => 'cartbuyer@example.com',
            'privacy_accepted' => true,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        $intent = $this->fakePaymentIntent('pi_cart_1', ['session_id' => $sessionId]);
        $this->invokeWebhookHandler($intent);

        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame('cartbuyer@example.com', $order->email);
        $this->assertSame('pi_cart_1', $order->stripe_payment_intent_id);
        $this->assertSame(OrderStatus::PAID, $order->order_status);

        $this->assertNull(Cart::find($cart->id));
        Mail::assertSent(OrderConfirmationMail::class);
    }

    public function test_it_is_idempotent_when_the_same_webhook_is_delivered_twice(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();
        $partner = $baseline['partner'];
        $product = $baseline['product'];
        $variant = $baseline['variant'];

        $sessionId = Str::random(40);

        $cart = Cart::create([
            'session_id' => $sessionId,
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'date' => $baseline['booking_date'],
            'time' => $baseline['booking_time'],
            'slot_type' => 'weekly',
            'slot_id' => $product->availabilities->first()->id,
            'total' => 10.00,
            'name' => 'Idempotent',
            'surname' => 'Buyer',
            'email' => 'idem@example.com',
            'privacy_accepted' => true,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        $intent = $this->fakePaymentIntent('pi_idem_1', ['session_id' => $sessionId]);
        $this->invokeWebhookHandler($intent);
        // Seconda consegna dello stesso webhook.
        $this->invokeWebhookHandler($intent);

        // Un solo ordine creato, una sola email inviata.
        $this->assertSame(1, Order::count());
        Mail::assertSent(OrderConfirmationMail::class, 1);
    }
}
