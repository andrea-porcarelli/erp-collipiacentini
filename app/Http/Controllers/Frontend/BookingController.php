<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderLogger;
use App\Services\ProductAvailabilityService;
use App\Services\ProductSeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private ProductAvailabilityService $availabilityService,
        private ProductSeoService $seoService,
        private OrderLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        $partner = $request->partner;
        $products = Product::where('is_active', 1)
            ->where('partner_id', $partner->id)
            ->with(['partner', 'category', 'contents.language', 'variants.prices', 'availabilities', 'cover', 'gallery'])
            ->get();

        $categories = Category::whereHas('products', fn ($q) => $q
            ->where('partner_id', $partner->id)
            ->where('is_active', 1))
            ->orderBy('label')
            ->get();

        $seo = $this->seoService->forListing($partner, $products);

        return view('whitelabel.index', compact('products', 'partner', 'seo', 'categories'));
    }

    public function filterProducts(Request $request): JsonResponse
    {
        $partner = $request->partner;
        $filter = $request->get('filter', 'all');
        $date = $request->get('date'); // Y-m-d or null

        $products = Product::where('is_active', 1)
            ->where('partner_id', $partner->id)
            ->with(['partner', 'category', 'contents.language', 'variants.prices', 'availabilities', 'cover', 'gallery'])
            ->when($filter !== 'all', fn ($q) => $q->where('category_id', $filter))
            ->get()
            ->when($date !== null, function ($collection) use ($date) {
                return $collection->filter(function (Product $product) use ($date) {
                    if ($this->availabilityService->isDateClosed($product, $date)) {
                        return false;
                    }
                    $slots = $this->availabilityService->getSlotsForDate($product, $date);

                    return $slots->contains(fn ($s) => is_null($s['availability']) || $s['availability'] > 0);
                });
            });

        $html = '';
        if ($products->isEmpty()) {
            $html = __('whitelabel.products.no_availability');
        } else {
            foreach ($products as $product) {
                $html .= view('components.whitelabel.product', ['product' => $product])->render();
            }
        }

        return response()->json(['html' => $html]);
    }

    public function page(Request $request, string $page, ?string $slug = null): View
    {
        $config = Partner::PAGES[$page] ?? null;

        if (! $config) {
            abort(404);
        }

        if ($slug !== null) {
            $partner = Partner::where('sale_method', 'whitelabel_no_domain')
                ->where(fn ($q) => $q->where('domain_name', $slug)->orWhere('slug_name', $slug))
                ->first();
        } else {
            $host = $request->getHost();
            $partner = Partner::where('sale_method', 'whitelabel_domain')
                ->where(fn ($q) => $q->where('domain_name', $host)->orWhere('slug_name', $host))
                ->first();
        }

        if (! $partner) {
            abort(404);
        }

        session()->put('partner', $partner);

        $title = $config['title'];
        $content = $partner->contentField($config['field']) ?? '';

        return view('whitelabel.page', compact('partner', 'title', 'content', 'page'));
    }

    public function product(Request $request, string $slugProduct, string $productCode): View|RedirectResponsez
    {
        // productCode format (see Product::getProductCodeAttribute):
        // {category_code}-{partner_code}{5-digit-zero-padded-id}
        // Example: "V-ROCCA00005" → category_code="V", partner_code="ROCCA", id=5
        [$categoryCode, $remainder] = array_pad(explode('-', $productCode, 2), 2, '');
        $productId = (int) substr($remainder, -5);
        $partnerCode = substr($remainder, 0, -5);

        $product = Product::with([
            'partner',
            'partner.media',
            'category',
            'contents.language',
            'variants.prices',
            'priceVariations',
            'closedPeriods',
            'gallery',
            'faqs',
            'features',
            'relatedProducts.relatedProduct.partner',
        ])
            ->where('id', $productId)
            ->whereHas('category', fn ($q) => $q->where('category_code', $categoryCode))
            ->whereHas('partner', fn ($q) => $q->where('partner_code', $partnerCode))
            ->first();

        if (! $product) {
            Log::info(__METHOD__.': Product not found', compact('productCode', 'categoryCode', 'partnerCode', 'productId'));
            abort(404);
        }

        if (! $product->is_active) {
            return redirect('/shop', 301);
        }

        $partner = $request->partner;
        $seo = $this->seoService->forProduct($product);

        return view('whitelabel.product', compact('product', 'partner', 'seo'));
    }

    /**
     * Returns available time slots for a product on a given date.
     * Each slot includes pricing information with any applicable price variation.
     */
    public function getAvailableTimes(Request $request, int $productId): JsonResponse
    {
        $date = $request->get('date');

        if (! $date) {
            return response()->json(['error' => 'Data non specificata'], 400);
        }

        $product = Product::with(['variants.prices', 'partner'])->find($productId);

        if (! $product) {
            return response()->json(['error' => 'Prodotto non trovato'], 404);
        }

        if ($this->availabilityService->isDateClosed($product, $date)) {
            return response()->json(['times' => [], 'closed' => true]);
        }

        $variation = $this->availabilityService->getApplicablePriceVariation($product, $date);
        $slots = $this->availabilityService->getSlotsForDate($product, $date);

        $times = $slots->map(function ($slot) use ($product, $variation) {
            if ($slot['slot_type'] === 'weekly') {
                $availabilityId = $slot['slot_id'];
                $variantCollection = $product->variants->where('availability_id', $availabilityId);
                if ($variantCollection->isEmpty()) {
                    $variantCollection = $product->variants
                        ->whereNull('availability_id')
                        ->whereNull('special_schedule_id');
                }
            } else {
                $variantCollection = $product->variants->where('special_schedule_id', $slot['slot_id']);
                if ($variantCollection->isEmpty()) {
                    $variantCollection = $product->variants
                        ->whereNull('availability_id')
                        ->whereNull('special_schedule_id');
                }
            }

            $variants = $variantCollection->map(function (ProductVariant $v) use ($product, $variation) {
                $basePrice = (float) $v->full_price;
                $price = $this->availabilityService->applyPriceVariation($basePrice, $variation);
                $baseCommission = $product->partner?->resolvePresaleCommission($basePrice) ?? 0;
                $priceCommission = $product->partner?->resolvePresaleCommission($price) ?? 0;

                return [
                    'id' => $v->id,
                    'label' => $v->label,
                    'base_price' => $basePrice + $baseCommission,
                    'price' => $price + $priceCommission,
                ];
            })->values();

            return [
                'time' => $slot['time'],
                'availability' => $slot['availability'],
                'slot_type' => $slot['slot_type'],
                'slot_id' => $slot['slot_id'],
                'is_available' => is_null($slot['availability']) || $slot['availability'] > 0,
                'variants' => $variants,
            ];
        });

        return response()->json([
            'times' => $times,
            'price_variation_id' => $variation?->id,
        ]);
    }

    /**
     * Returns available days for a calendar month view.
     */
    public function getAvailableDays(Request $request, int $productId): JsonResponse
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $product = Product::find($productId);

        if (! $product) {
            return response()->json(['error' => 'Prodotto non trovato'], 404);
        }

        $days = $this->availabilityService->getAvailableDaysForMonth($product, $year, $month);

        return response()->json(['days' => $days]);
    }

    /**
     * Add items to cart.
     * Request: product_id, date, time, items: [{variant_id, quantity}]
     */
    public function addToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $product = Product::with(['variants.prices', 'partner'])->findOrFail($validated['product_id']);

        // Check closed periods
        if ($this->availabilityService->isDateClosed($product, $validated['date'])) {
            return response()->json(['error' => 'Il prodotto non è disponibile in questa data'], 400);
        }

        // Find the matching slot
        $slot = $this->availabilityService->getSlot($product, $validated['date'], $validated['time']);

        if (! $slot) {
            return response()->json(['error' => 'Orario non disponibile per questa data'], 400);
        }

        // Check total quantity against slot availability
        $totalQuantity = collect($validated['items'])->sum('quantity');

        if (! is_null($slot['availability']) && $slot['availability'] < $totalQuantity) {
            return response()->json(['error' => 'Disponibilità insufficiente per l\'orario selezionato'], 400);
        }

        if ($product->max_tickets_per_session && $totalQuantity > $product->max_tickets_per_session) {
            return response()->json([
                'error' => "Puoi acquistare al massimo {$product->max_tickets_per_session} biglietti per prenotazione",
            ], 400);
        }

        // Find applicable price variation
        $variation = $this->availabilityService->getApplicablePriceVariation($product, $validated['date']);
        $variantMap = $product->variants->keyBy('id');

        // Build cart items with unit prices
        $cartItemsData = [];
        $total = 0;

        foreach ($validated['items'] as $item) {
            $variant = $variantMap->get($item['variant_id']);
            if (! $variant) {
                return response()->json(['error' => 'Variante non trovata'], 404);
            }

            $unitPrice = $this->availabilityService->applyPriceVariation($variant->full_price, $variation);
            $unitPrice += $product->partner?->resolvePresaleCommission($unitPrice) ?? 0;
            $cartItemsData[] = [
                'product_variant_id' => $variant->id,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
            ];
            $total += $unitPrice * $item['quantity'];
        }

        $sessionId = session()->getId();

        DB::beginTransaction();
        try {
            // Se esisteva un cart precedente, logghiamo che è stato svuotato
            // prima di sovrascriverlo (cliente che cambia idea su data/varianti).
            $previousCart = Cart::where('session_id', $sessionId)->first();
            if ($previousCart) {
                $this->logger->logCartRemoved($previousCart, 'sostituito da nuovo carrello');
                $previousCart->delete();
            }

            $cart = Cart::create([
                'session_id' => $sessionId,
                'partner_id' => $request->partner?->id ?? $product->partner_id,
                'product_id' => $product->id,
                'date' => $validated['date'],
                'time' => $validated['time'],
                'slot_type' => $slot['slot_type'],
                'slot_id' => $slot['slot_id'],
                'applied_price_variation_id' => $variation?->id,
                'total' => round($total, 2),
            ]);

            foreach ($cartItemsData as $itemData) {
                CartItem::create(array_merge(['cart_id' => $cart->id], $itemData));
            }

            $itemsSummary = collect($cartItemsData)->map(fn ($i) => [
                'variant_id' => $i['product_variant_id'],
                'quantity'   => $i['quantity'],
                'unit_price' => $i['unit_price'],
            ])->all();
            $this->logger->logCartStarted($cart, $itemsSummary, round($total, 2));

            DB::commit();

            return response()->json([
                'success' => true,
                'cart_id' => $cart->id,
                'redirect_url' => route('booking.cart'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Errore durante l\'aggiunta al carrello'], 500);
        }
    }

    public function cart(Request $request): View
    {
        $sessionId = session()->getId();
        $cart = Cart::with(['product.partner', 'product.variants.prices', 'items.variant', 'appliedPriceVariation', 'product.customerFields.fieldType'])
            ->where('session_id', $sessionId)
            ->first();

        $partner = $request->partner;

        $partnerConsents = ($partner && $partner->consents_enabled)
            ? $partner->consents()->where('is_active', true)->orderBy('position')->get()
            : collect();

        return view('whitelabel.cart', compact('cart', 'partner', 'partnerConsents'));
    }

    public function removeCart(Request $request): JsonResponse
    {
        $sessionId = session()->getId();
        $cart = Cart::where('session_id', $sessionId)->first();

        if (! $cart) {
            return response()->json(['error' => 'Carrello non trovato'], 404);
        }

        $this->logger->logCartRemoved($cart, 'rimosso dal cliente');
        $cart->delete();

        return response()->json([
            'success' => true,
            'redirect_url' => url('/shop'),
        ]);
    }

    public function saveCustomer(Request $request): JsonResponse
    {
        $sessionId = session()->getId();
        $cart = Cart::with(['partner', 'product.customerFields.fieldType'])
            ->where('session_id', $sessionId)
            ->first();

        if (! $cart) {
            return response()->json(['error' => 'Carrello non trovato'], 404);
        }

        // Mappa key del field-type → colonna Customer + regola di base
        $fieldDefinitions = [
            'address' => ['column' => 'address',     'rule' => 'string|max:255'],
            'birth_date' => ['column' => 'birth_date',  'rule' => 'date'],
            'phone' => ['column' => 'phone',       'rule' => 'string|max:20'],
            'tax_code' => ['column' => 'fiscal_code', 'rule' => 'string|max:16'],
        ];

        $partnerConsents = ($cart->partner && $cart->partner->consents_enabled)
            ? $cart->partner->consents()->where('is_active', true)->get()
            : collect();

        $rules = [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'privacy' => $partnerConsents->isNotEmpty() ? 'nullable' : 'required|accepted',
            'newsletter' => 'nullable|boolean',
            'consents' => 'array',
        ];

        foreach ($partnerConsents as $pc) {
            $rules['consents.'.$pc->id] = $pc->is_required ? 'required|accepted' : 'nullable|boolean';
        }

        $activeColumns = [];
        foreach ($cart->product->customerFields as $field) {
            $def = $fieldDefinitions[$field->fieldType->key] ?? null;
            if (! $def) {
                continue;
            }
            $rules[$def['column']] = ($field->is_required ? 'required|' : 'nullable|').$def['rule'];
            $activeColumns[] = $def['column'];
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $user = Customer::where('email', $validated['email'])->first();

            $customerData = [
                'name' => $validated['name'],
                'surname' => $validated['surname'],
            ];
            foreach ($activeColumns as $column) {
                if (! empty($validated[$column])) {
                    $customerData[$column] = $validated[$column];
                }
            }

            if (! $user) {
                $user = Customer::create(array_merge($customerData, [
                    'email' => $validated['email'],
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'customer',
                    'company_id' => $cart->partner?->company_id,
                    'partner_id' => $cart->partner_id,
                    'privacy_accepted' => true,
                    'newsletter' => $validated['newsletter'] ?? false,
                ]));
            } else {
                $customerData['newsletter'] = $validated['newsletter'] ?? $user->newsletter;
                $user->update($customerData);
            }

            $cartUpdate = ['customer_id' => $user->id];

            if ($partnerConsents->isNotEmpty()) {
                $cartUpdate['consents_payload'] = $this->buildConsentsPayload($partnerConsents, $validated['consents'] ?? []);
            }

            $cart->update($cartUpdate);

            // Attribuiamo i log al Customer appena collegato.
            $this->logger->as($user)->logCartCustomerAssigned($cart, $user);
            if (! empty($cartUpdate['consents_payload'])) {
                $this->logger->as($user)->logCartConsentsAccepted($cart, $cartUpdate['consents_payload']);
            }
            $this->logger->endBatch();

            DB::commit();

            return response()->json([
                'success' => true,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Errore durante il salvataggio dei dati: '.$e->getMessage()], 500);
        }
    }

    private function buildConsentsPayload($partnerConsents, array $consentInput): array
    {
        $now = now();

        return $partnerConsents->map(function ($pc) use ($now, $consentInput) {
            $accepted = $pc->is_required
                ? true
                : (bool) ($consentInput[$pc->id] ?? false);

            return [
                'partner_consent_id' => $pc->id,
                'accepted'           => $accepted,
                'subscribed_at'      => $now->toIso8601String(),
                'expires_at'         => $pc->computeExpiresAt($now)?->toIso8601String(),
            ];
        })->values()->all();
    }
}
