<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\OrderConsent;
use App\Models\PartnerConsent;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifica che il versionamento SCD Type 2 su partner_consents preservi il
 * contenuto storico: un ordine passato continua a puntare alla versione
 * accettata al momento del checkout anche se il testo viene modificato dopo.
 */
class PartnerConsentVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_order_consent_still_points_to_the_historical_version(): void
    {
        Mail::fake();

        $baseline = $this->seedBaseline();
        $partner = $baseline['partner'];
        $product = $baseline['product'];
        $variant = $baseline['variant'];

        $originalConsent = PartnerConsent::create([
            'partner_id' => $partner->id,
            'code' => 'privacy',
            'version' => 1,
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
            'name' => 'Storico',
            'surname' => 'Cliente',
            'email' => 'storico@example.com',
            'privacy_accepted' => true,
            'consents_payload' => [[
                'partner_consent_id' => $originalConsent->id,
                'accepted' => true,
                'subscribed_at' => now()->toDateTimeString(),
                'expires_at' => now()->addYears(2)->toDateTimeString(),
            ]],
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        $order = app(OrderService::class)->createOrderFromCart($cart->fresh());

        // Simuliamo la modifica del testo: si crea una nuova versione e la
        // vecchia riceve superseded_at + superseded_by_id.
        $newVersion = PartnerConsent::create([
            'partner_id' => $partner->id,
            'code' => 'privacy',
            'version' => 2,
            'is_required' => true,
            'is_locked' => false,
            'is_active' => true,
            'expiry_days' => 0,
            'expiry_months' => 0,
            'expiry_years' => 2,
            'position' => 1,
        ]);
        $originalConsent->update([
            'superseded_at' => now(),
            'superseded_by_id' => $newVersion->id,
        ]);

        // L'ordine storico continua a puntare alla versione 1.
        $orderConsent = OrderConsent::where('order_id', $order->id)->first();
        $this->assertSame($originalConsent->id, $orderConsent->partner_consent_id);
        $this->assertSame(1, (int) $orderConsent->partnerConsent->version);
        $this->assertNotNull($orderConsent->partnerConsent->superseded_at);

        // Il lookup "corrente" per un nuovo cart restituisce solo la versione 2.
        $currentIds = $partner->consents()->current()->pluck('id')->all();
        $this->assertContains($newVersion->id, $currentIds);
        $this->assertNotContains($originalConsent->id, $currentIds);
    }
}
