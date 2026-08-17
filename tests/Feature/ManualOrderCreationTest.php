<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManualOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function manualPayload(array $baseline, array $customerOverrides = [], array $overrides = []): array
    {
        $customer = array_merge([
            'name' => 'Luca',
            'surname' => 'Verdi',
            'email' => 'luca.verdi@example.com',
            'phone' => '3339876543',
            'prefix_phone' => '+39',
            'address' => 'Via Test 1',
            'city' => 'Milano',
            'zip_code' => '20100',
            'fiscal_code' => 'VRDLCU80A01F205X',
        ], $customerOverrides);

        return array_merge([
            'partner_id' => $baseline['partner']->id,
            'product_id' => $baseline['product']->id,
            'date' => $baseline['booking_date'],
            'time' => $baseline['booking_time'],
            'items' => [
                ['variant_id' => $baseline['variant']->id, 'quantity' => 2],
            ],
            'customer' => $customer,
            'order_status' => 'pending',
            'send_email' => false,
        ], $overrides);
    }

    public function test_it_creates_a_manual_order_with_denormalized_customer_data(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();

        $service = app(OrderService::class);
        $order = $service->createOrderManually($this->manualPayload($baseline));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'partner_id' => $baseline['partner']->id,
            'order_status' => OrderStatus::PENDING->value,
            'email' => 'luca.verdi@example.com',
            'name' => 'Luca',
            'surname' => 'Verdi',
            'fiscal_code' => 'VRDLCU80A01F205X',
            'city' => 'Milano',
        ]);

        $this->assertEquals('20.00', $order->amount);

        Mail::assertNotSent(OrderConfirmationMail::class);
    }

    public function test_a_new_order_does_not_alter_previous_orders_of_the_same_email(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();
        $service = app(OrderService::class);

        // Primo ordine
        $first = $service->createOrderManually($this->manualPayload(
            $baseline,
            ['name' => 'Luca', 'surname' => 'Verdi', 'city' => 'Milano']
        ));

        // Secondo ordine stessa email, dati diversi
        $second = $service->createOrderManually($this->manualPayload(
            $baseline,
            ['name' => 'Luca', 'surname' => 'Verdi', 'city' => 'Bologna']
        ));

        $this->assertSame(2, Order::count());
        $this->assertSame('Milano', $first->fresh()->city);
        $this->assertSame('Bologna', $second->fresh()->city);
    }

    public function test_it_sends_confirmation_email_when_order_paid_and_send_email_flag_true(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();

        $service = app(OrderService::class);
        $service->createOrderManually($this->manualPayload(
            $baseline,
            [],
            ['order_status' => 'paid', 'send_email' => true]
        ));

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) {
            return $mail->hasTo('luca.verdi@example.com');
        });

        $order = Order::first();
        $this->assertSame(OrderStatus::PAID, $order->order_status);
        $this->assertNotNull($order->paid_at);
    }
}
