<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\OrderConsent;
use App\Models\OrderParticipant;
use App\Models\OrderProduct;
use App\Models\OrderProductItem;
use App\Models\PartnerConsent;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Il flusso guest checkout: cart popolato con customer e items, poi
     * OrderService::createOrderFromCart trasferisce tutto sull'ordine.
     * Completa quindi con completeOrder che invia l'email di conferma.
     */
    public function test_it_creates_order_from_cart_and_sends_confirmation_email(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();
        $partner = $baseline['partner'];
        $product = $baseline['product'];
        $variant = $baseline['variant'];

        $cart = Cart::create([
            'session_id' => Str::random(40),
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'date' => $baseline['booking_date'],
            'time' => $baseline['booking_time'],
            'slot_type' => 'weekly',
            'slot_id' => $product->availabilities->first()->id,
            'total' => 20.00,
            'name' => 'Mario',
            'surname' => 'Rossi',
            'email' => 'mario.rossi@example.com',
            'phone' => '3331234567',
            'privacy_accepted' => true,
            'newsletter' => false,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 10.00,
        ]);

        $service = app(OrderService::class);
        $order = $service->createOrderFromCart(
            $cart->fresh(),
            'pi_test_12345',
            'pm_test_12345'
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'partner_id' => $partner->id,
            'stripe_payment_intent_id' => 'pi_test_12345',
            'stripe_payment_method' => 'pm_test_12345',
            'order_status' => OrderStatus::PENDING->value,
            'name' => 'Mario',
            'surname' => 'Rossi',
            'email' => 'mario.rossi@example.com',
            'phone' => '3331234567',
        ]);
        $this->assertEquals('20.00', $order->amount);
        $this->assertStringStartsWith('ORD-TT-', $order->order_number);

        // Un OrderProduct + un OrderProductItem + due OrderParticipant (uno per biglietto).
        $this->assertSame(1, OrderProduct::where('order_id', $order->id)->count());
        $orderProduct = OrderProduct::where('order_id', $order->id)->first();
        $this->assertSame($product->id, $orderProduct->product_id);
        $this->assertSame(2, (int) $orderProduct->quantity);

        $this->assertSame(1, OrderProductItem::where('order_product_id', $orderProduct->id)->count());
        $opi = OrderProductItem::where('order_product_id', $orderProduct->id)->first();
        $this->assertSame($variant->id, $opi->product_variant_id);
        $this->assertSame(2, (int) $opi->quantity);

        $this->assertSame(2, OrderParticipant::where('order_id', $order->id)->count());

        $completed = $service->completeOrder($order->fresh(), 'pm_test_12345');
        $this->assertSame(OrderStatus::PAID, $completed->order_status);
        $this->assertNotNull($completed->paid_at);

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) {
            return $mail->hasTo('mario.rossi@example.com');
        });
    }

    /**
     * I consensi accettati vengono salvati come snapshot per-ordine su
     * order_consents. La riga è immutabile: una versione futura di
     * partner_consent non intacca il record storico.
     */
    public function test_it_snapshots_cart_consents_on_order_creation(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();
        $partner = $baseline['partner'];
        $product = $baseline['product'];
        $variant = $baseline['variant'];

        $partnerConsent = PartnerConsent::create([
            'partner_id' => $partner->id,
            'code' => 'privacy',
            'is_required' => true,
            'is_locked' => false,
            'is_active' => true,
            'expiry_days' => 0,
            'expiry_months' => 0,
            'expiry_years' => 2,
            'position' => 1,
        ]);

        $cart = Cart::create([
            'session_id' => Str::random(40),
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'date' => $baseline['booking_date'],
            'time' => $baseline['booking_time'],
            'slot_type' => 'weekly',
            'slot_id' => $product->availabilities->first()->id,
            'total' => 10.00,
            'name' => 'Anna',
            'surname' => 'Bianchi',
            'email' => 'anna.bianchi@example.com',
            'privacy_accepted' => true,
            'consents_payload' => [
                [
                    'partner_consent_id' => $partnerConsent->id,
                    'accepted' => true,
                    'subscribed_at' => now()->toDateTimeString(),
                    'expires_at' => now()->addYears(2)->toDateTimeString(),
                ],
            ],
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        $service = app(OrderService::class);
        $order = $service->createOrderFromCart($cart->fresh());

        $this->assertDatabaseHas('order_consents', [
            'order_id' => $order->id,
            'partner_consent_id' => $partnerConsent->id,
            'partner_id' => $partner->id,
            'accepted' => 1,
        ]);
        $this->assertSame(1, OrderConsent::where('order_id', $order->id)->count());
    }
}
