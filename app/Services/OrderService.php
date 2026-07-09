<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerConsent;
use App\Models\Order;
use App\Models\OrderParticipant;
use App\Models\OrderProduct;
use App\Models\OrderProductItem;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Notifications\NewOrderTelegramNotify;
use App\Services\OrderLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private ProductAvailabilityService $availabilityService,
        private OrderLogger $logger,
    ) {}

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
                $opi = OrderProductItem::create([
                    'order_product_id'  => $orderProduct->id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                    'unit_price'         => $item->unit_price,
                    ...$commissionSnapshot,
                ]);

                // Una riga participant per ogni biglietto, stato di default "Prenotato".
                for ($i = 0; $i < (int) $item->quantity; $i++) {
                    OrderParticipant::create([
                        'order_id'              => $order->id,
                        'order_product_item_id' => $opi->id,
                        'status'                => 'booked',
                    ]);
                }
            }

            $this->snapshotCartConsents($cart, $order, $customer);

            // Promuovi i log del cart sull'ordine appena creato e logga la nascita
            // dell'ordine attribuendola al customer (o al sistema se manca).
            $this->logger->promoteCartLogs($cart, $order);
            $this->logger->as($customer)->logOrderCreated($order);
            $this->logger->endBatch();

            return $order;
        });
    }

    /**
     * Upsert dei consensi raccolti nel carrello.
     *
     * Modello: un solo record per (customer_id, partner_consent_id). Su riacquisto
     * lasciamo invariato il subscribed_at originario (data del primo consenso) e
     * aggiorniamo solo expires_at e accepted. Se il consenso non esiste ancora,
     * viene creato con subscribed_at = ora corrente.
     */
    protected function snapshotCartConsents(Cart $cart, Order $order, Customer $customer): void
    {
        $payload = $cart->consents_payload;
        if (! is_array($payload) || empty($payload)) {
            return;
        }

        foreach ($payload as $entry) {
            if (! is_array($entry) || empty($entry['partner_consent_id'])) {
                continue;
            }

            $existing = CustomerConsent::where('customer_id', $customer->id)
                ->where('partner_consent_id', $entry['partner_consent_id'])
                ->first();

            if ($existing) {
                $existing->update([
                    'partner_id' => $cart->partner_id,
                    'accepted'   => (bool) ($entry['accepted'] ?? false),
                    'expires_at' => $entry['expires_at'] ?? null,
                ]);
            } else {
                CustomerConsent::create([
                    'customer_id'        => $customer->id,
                    'partner_consent_id' => $entry['partner_consent_id'],
                    'partner_id'         => $cart->partner_id,
                    'accepted'           => (bool) ($entry['accepted'] ?? false),
                    'subscribed_at'      => $entry['subscribed_at'] ?? now(),
                    'expires_at'         => $entry['expires_at'] ?? null,
                ]);
            }
        }
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
            $this->logger->logOrderPaid($order);
            $this->sendConfirmationEmail($order);
            $this->notifyTelegramNewOrder($order);
        }

        return $order;
    }

    protected function notifyTelegramNewOrder(Order $order): void
    {
        try {
            $chatId = config('services.telegram.group_id');
            if (empty($chatId)) {
                return;
            }

            Notification::route('telegram', $chatId)
                ->notify(new NewOrderTelegramNotify($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function sendConfirmationEmail(Order $order): void
    {
        try {
            $order->loadMissing(['customer', 'partner', 'orderProducts.product.category', 'orderProducts.items.variant']);
            if ($order->customer?->email) {
                Mail::to($order->customer->email)->send(new OrderConfirmationMail($order));
                $this->logger->logEmailSent($order, $order->customer->email);
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

        $this->logger->logOrderFailed($order, $errorMessage);

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

    /**
     * Crea un ordine manualmente dal backoffice, senza Cart né Stripe.
     *
     * $payload:
     *   - partner_id: int
     *   - product_id: int
     *   - date: 'Y-m-d'
     *   - time: 'H:i'
     *   - items: [ ['variant_id' => int, 'quantity' => int], ... ]
     *   - customer: [
     *         'id' => ?int, 'name', 'surname', 'email', 'phone', 'prefix_phone',
     *         'address', 'city', 'zip_code', 'fiscal_code'
     *     ]
     *   - order_status: 'pending' | 'paid'
     *   - send_email: bool
     *
     * L'operatore ha già visto il prezzo calcolato lato server: qui ricalcoliamo
     * comunque per evitare che dati manomessi lato client alterino l'importo.
     */
    public function createOrderManually(array $payload): Order
    {
        $partner = Partner::findOrFail($payload['partner_id']);
        $product = Product::with(['variants.prices', 'partner'])->findOrFail($payload['product_id']);

        if ($product->partner_id !== $partner->id) {
            throw new \InvalidArgumentException('Il prodotto selezionato non appartiene al partner scelto');
        }

        if ($this->availabilityService->isDateClosed($product, $payload['date'])) {
            throw new \InvalidArgumentException('Il prodotto non è disponibile in questa data');
        }

        $slot = $this->availabilityService->getSlot($product, $payload['date'], $payload['time']);
        if (! $slot) {
            throw new \InvalidArgumentException('Orario non disponibile per questa data');
        }

        $totalQuantity = collect($payload['items'])->sum('quantity');
        if (! is_null($slot['availability']) && $slot['availability'] < $totalQuantity) {
            throw new \InvalidArgumentException('Disponibilità insufficiente per l\'orario selezionato');
        }

        if ($product->max_tickets_per_session && $totalQuantity > $product->max_tickets_per_session) {
            throw new \InvalidArgumentException(sprintf(
                'Puoi registrare al massimo %d biglietti per prenotazione',
                $product->max_tickets_per_session
            ));
        }

        $variation = $this->availabilityService->getApplicablePriceVariation($product, $payload['date']);
        $variantMap = $product->variants->keyBy('id');

        $itemsResolved = [];
        $total = 0;
        foreach ($payload['items'] as $item) {
            $variant = $variantMap->get($item['variant_id']);
            if (! $variant) {
                throw new \InvalidArgumentException('Variante non trovata');
            }
            $unitPrice = $this->availabilityService->applyPriceVariation((float) $variant->full_price, $variation);
            $unitPrice += $partner->resolvePresaleCommission($unitPrice) ?? 0;
            $itemsResolved[] = [
                'variant_id' => $variant->id,
                'quantity'   => (int) $item['quantity'],
                'unit_price' => $unitPrice,
            ];
            $total += $unitPrice * (int) $item['quantity'];
        }
        $total = round($total, 2);

        return DB::transaction(function () use ($partner, $product, $payload, $itemsResolved, $total, $variation, $slot, $totalQuantity) {
            $customer = $this->upsertCustomer($payload['customer'], $partner);

            $orderStatus = OrderStatus::from($payload['order_status']);
            $orderNumber = $this->generateOrderNumber($partner->partner_code);

            $order = Order::create([
                'customer_id'  => $customer->id,
                'partner_id'   => $partner->id,
                'order_number' => $orderNumber,
                'amount'       => $total,
                'order_status' => $orderStatus,
                'paid_at'      => $orderStatus === OrderStatus::PAID ? now() : null,
            ]);

            $orderProduct = OrderProduct::create([
                'order_id'                   => $order->id,
                'product_id'                 => $product->id,
                'booking_date'               => $payload['date'],
                'booking_time'               => $payload['time'],
                'slot_type'                  => $slot['slot_type'],
                'slot_id'                    => $slot['slot_id'],
                'applied_price_variation_id' => $variation?->id,
                'price'                      => $total,
                'quantity'                   => $totalQuantity,
                'total'                      => $total,
            ]);

            $commissionSnapshot = $total > 0 ? [
                'partner_commission_presale_low'       => $partner->commission_presale_low,
                'partner_commission_presale_high'      => $partner->commission_presale_high,
                'partner_commission_presale_threshold' => $partner->commission_presale_threshold,
                'partner_commission_miticko_fixed'     => $partner->commission_miticko_fixed,
                'partner_commission_miticko_variable'  => $partner->commission_miticko_variable,
                'partner_commission_payment'           => $partner->commission_payment,
            ] : [];

            foreach ($itemsResolved as $item) {
                $opi = OrderProductItem::create([
                    'order_product_id'   => $orderProduct->id,
                    'product_variant_id' => $item['variant_id'],
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['unit_price'],
                    ...$commissionSnapshot,
                ]);

                for ($i = 0; $i < $item['quantity']; $i++) {
                    OrderParticipant::create([
                        'order_id'              => $order->id,
                        'order_product_item_id' => $opi->id,
                        'status'                => 'booked',
                    ]);
                }
            }

            $this->logger->logOrderCreated($order);

            if ($orderStatus === OrderStatus::PAID) {
                $this->logger->logOrderPaid($order);
                if (! empty($payload['send_email'])) {
                    $this->sendConfirmationEmail($order);
                }
            }

            $this->logger->endBatch();

            return $order->fresh();
        });
    }

    /**
     * Trova o crea il Customer per l'ordine manuale.
     *
     * Se è stato passato un customer.id, viene aggiornato con i nuovi dati.
     * Altrimenti si cerca per email; se non esiste, viene creato.
     */
    protected function upsertCustomer(array $data, Partner $partner): Customer
    {
        $fields = [
            'name'         => $data['name'] ?? null,
            'surname'      => $data['surname'] ?? null,
            'email'        => $data['email'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'prefix_phone' => $data['prefix_phone'] ?? null,
            'address'      => $data['address'] ?? null,
            'city'         => $data['city'] ?? null,
            'zip_code'     => $data['zip_code'] ?? null,
            'fiscal_code'  => $data['fiscal_code'] ?? null,
        ];
        $fields = array_filter($fields, fn ($v) => $v !== null && $v !== '');

        if (! empty($data['id'])) {
            $customer = Customer::findOrFail($data['id']);
            $customer->update($fields);
            return $customer->fresh();
        }

        if (! empty($fields['email'])) {
            $existing = Customer::where('email', $fields['email'])->first();
            if ($existing) {
                $existing->update($fields);
                return $existing->fresh();
            }
        }

        return Customer::create(array_merge($fields, [
            'partner_id'       => $partner->id,
            'company_id'       => $partner->company_id,
            'privacy_accepted' => true,
            'newsletter'       => false,
        ]));
    }

    /**
     * Restituisce l'URL di pagamento Stripe per un ordine pending, generandolo
     * se non presente. Riusa l'URL già memorizzato per evitare Checkout Session duplicate.
     */
    public function ensurePaymentLink(Order $order): string
    {
        if ($order->stripe_payment_link_url) {
            return $order->stripe_payment_link_url;
        }

        $session = app(StripePaymentService::class)->createPaymentLinkForOrder($order);

        $order->update([
            'stripe_payment_link_id'  => $session->id,
            'stripe_payment_link_url' => $session->url,
        ]);

        return $session->url;
    }

    /**
     * Calcola prezzo e varianti disponibili per una combinazione product/date/time.
     * Usato dall'UI di creazione ordine per mostrare i prezzi correnti prima del submit.
     */
    public function quoteVariants(Product $product, string $date, string $time): array
    {
        $slot = $this->availabilityService->getSlot($product, $date, $time);
        if (! $slot) {
            return ['slot' => null, 'variants' => []];
        }

        $variation = $this->availabilityService->getApplicablePriceVariation($product, $date);

        if ($slot['slot_type'] === 'weekly') {
            $variants = $product->variants->where('availability_id', $slot['slot_id']);
            if ($variants->isEmpty()) {
                $variants = $product->variants
                    ->whereNull('availability_id')
                    ->whereNull('special_schedule_id');
            }
        } else {
            $variants = $product->variants->where('special_schedule_id', $slot['slot_id']);
            if ($variants->isEmpty()) {
                $variants = $product->variants
                    ->whereNull('availability_id')
                    ->whereNull('special_schedule_id');
            }
        }

        $partner = $product->partner;

        $mapped = $variants->map(function (ProductVariant $v) use ($variation, $partner) {
            $basePrice = (float) $v->full_price;
            $price = $this->availabilityService->applyPriceVariation($basePrice, $variation);
            $priceWithCommission = $price + ($partner?->resolvePresaleCommission($price) ?? 0);

            return [
                'id'    => $v->id,
                'label' => $v->label,
                'price' => round($priceWithCommission, 2),
            ];
        })->values()->all();

        return [
            'slot' => [
                'time'         => $slot['time'],
                'slot_type'    => $slot['slot_type'],
                'slot_id'      => $slot['slot_id'],
                'availability' => $slot['availability'],
            ],
            'variants' => $mapped,
        ];
    }

}
