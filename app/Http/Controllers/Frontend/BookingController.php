<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{

    public function index(Request $request) : View {
        $company = $request->company;
        $products = $company->active_partners->flatMap(function ($partner) {
            return $partner->active_products()
                ->with(['partner.company', 'category', 'contents.language', 'prices', 'availabilities'])
                ->get()
                ->map(function ($product) {
                    return $product;
                });
        });
        return view('whitelabel.index' , compact('products', 'company'));
    }

    public function filterProducts(Request $request) : JsonResponse {
        $company = $request->company;
        $filter = $request->get('filter', 'all');
        $date = $request->get('date', null);

        $products = $company->active_partners->flatMap(function ($partner) use($date, $filter) {
            return $partner->active_products()
                ->with(['partner.company', 'category', 'contents.language', 'prices', 'availabilities'])
                ->when(isset($date), function ($query) use ($date) {
                    return $query->whereHas('availabilities', function ($query) use ($date) {
                        return $query->where('date', $date)
                            ->where('availability', '>', 0);
                    });
                })
                ->when($filter !== 'all', fn($q) => $q->whereDate('product_type', $filter))
                ->get()->map(function ($product) {
                return $product;
            });
        });

        // Genera l'HTML dei prodotti
        $html = '';
        if ($products->count() === 0) {
            $html = __('whitelabel.products.no_availability');
        } else {
            foreach ($products as $product) {
                $html .= view('components.whitelabel.product', ['product' => $product])->render();
            }
        }

        return response()->json(['html' => $html]);
    }

    public function product(Request $request, $slugPartner, $slugProduct, $productCode) : View
    {
        $productCode_ex = explode('-', $productCode);
        $productId = (int) substr($productCode_ex[count($productCode_ex) - 1], 2);

        // Trova il prodotto tramite product_code
        $product = Product::with(['partner.company', 'partner.media', 'category', 'contents.language', 'prices', 'availabilities', 'media'])
            ->where('id', $productId)
            ->first();
        $company = $product->partner->company;
        if (!$product) {
            abort(404);
        }

        // Ottieni i prezzi del prodotto
        $productPrices = $product->prices->first();

        return view('whitelabel.product', compact('product', 'company', 'productPrices'));
    }

    public function getAvailableTimes(Request $request, $productId) : JsonResponse
    {
        $date = $request->get('date');

        if (!$date) {
            return response()->json(['error' => 'Data non specificata'], 400);
        }

        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Prodotto non trovato'], 404);
        }

        // Ottieni gli ID dei prodotti collegati (incluso questo prodotto)
        $productIds = array_merge(
            [$product->id],
            $product->linked_product_ids ?? []
        );

        // Recupera tutte le availabilities per la data selezionata (anche quelle con availability = 0)
        $availabilities = \App\Models\ProductAvailability::whereIn('product_id', $productIds)
            ->where('date', $date)
            ->whereNotNull('time')
            ->orderBy('time', 'ASC')
            ->get();

        $times = $availabilities->map(function($availability) {
            return [
                'id' => $availability->id,
                'time' => $availability->time,
                'availability' => $availability->availability,
                'formatted_time' => \Carbon\Carbon::parse($availability->time)->format('H:i'),
                'is_available' => $availability->availability > 0
            ];
        });

        return response()->json(['times' => $times]);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'availability_id' => 'required|exists:product_availabilities,id',
            'quantity_full' => 'required|integer|min:0',
            'quantity_reduced' => 'required|integer|min:0',
            'quantity_free' => 'required|integer|min:0',
        ]);

        $totalQuantity = $validated['quantity_full'] + $validated['quantity_reduced'] + $validated['quantity_free'];
        if ($totalQuantity <= 0) {
            return response()->json(['error' => 'Seleziona almeno un biglietto'], 400);
        }

        $product = Product::with('prices')->find($validated['product_id']);
        $availability = ProductAvailability::find($validated['availability_id']);

        if (!$product || !$availability) {
            return response()->json(['error' => 'Prodotto o disponibilità non trovati'], 404);
        }

        if ($availability->availability < $totalQuantity) {
            return response()->json(['error' => 'Disponibilità insufficiente'], 400);
        }

        $prices = $product->prices->first();
        $priceFull = $prices->price ?? 0;
        $priceReduced = $prices->reduced ?? 0;
        $priceFree = $prices->free ?? 0;

        $total = ($validated['quantity_full'] * $priceFull) +
                 ($validated['quantity_reduced'] * $priceReduced) +
                 ($validated['quantity_free'] * $priceFree);

        $sessionId = session()->getId();

        DB::beginTransaction();
        try {
            // Rimuovi il carrello esistente per questa sessione (mono-prodotto)
            Cart::where('session_id', $sessionId)->delete();

            // Crea il nuovo carrello
            $cart = Cart::create([
                'session_id' => $sessionId,
                'company_id' => $product->partner->company_id,
                'product_id' => $product->id,
                'product_availability_id' => $availability->id,
                'date' => $availability->date,
                'time' => $availability->time,
                'quantity_full' => $validated['quantity_full'],
                'quantity_reduced' => $validated['quantity_reduced'],
                'quantity_free' => $validated['quantity_free'],
                'price_full' => $priceFull,
                'price_reduced' => $priceReduced,
                'price_free' => $priceFree,
                'total' => $total,
            ]);

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
        $cart = Cart::with(['product.partner.company', 'product.prices', 'productAvailability'])
            ->where('session_id', $sessionId)
            ->first();

        $company = $cart?->product?->partner?->company ?? $request->company;

        return view('whitelabel.cart', compact('cart', 'company'));
    }

    public function removeCart(Request $request): JsonResponse
    {
        $sessionId = session()->getId();
        $cart = Cart::where('session_id', $sessionId)->first();

        if (!$cart) {
            return response()->json(['error' => 'Carrello non trovato'], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'redirect_url' => url('/shop'),
        ]);
    }

    public function saveCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:255',
            'zip_code' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'phone' => 'required|string|max:20',
            'fiscal_code' => 'nullable|string|max:16',
            'birth_date' => 'nullable|date',
            'privacy' => 'required|accepted',
            'newsletter' => 'nullable|boolean',
        ]);

        $sessionId = session()->getId();
        $cart = Cart::with('product.partner')->where('session_id', $sessionId)->first();

        if (!$cart) {
            return response()->json(['error' => 'Carrello non trovato'], 404);
        }

        DB::beginTransaction();
        try {
            // Cerca utente esistente per email o crea nuovo
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $validated['name'],
                    'surname' => $validated['surname'],
                    'email' => $validated['email'],
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'customer',
                    'company_id' => $cart->company_id,
                    'partner_id' => $cart->product->partner_id,
                    'address' => $validated['address'],
                    'zip_code' => $validated['zip_code'],
                    'city' => $validated['city'],
                    'country' => $validated['country'],
                    'phone' => $validated['phone'],
                    'fiscal_code' => $validated['fiscal_code'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'privacy_accepted' => true,
                    'newsletter' => $validated['newsletter'] ?? false,
                ]);
            } else {
                // Aggiorna i dati dell'utente esistente
                $user->update([
                    'name' => $validated['name'],
                    'surname' => $validated['surname'],
                    'address' => $validated['address'],
                    'zip_code' => $validated['zip_code'],
                    'city' => $validated['city'],
                    'country' => $validated['country'],
                    'phone' => $validated['phone'],
                    'fiscal_code' => $validated['fiscal_code'] ?? $user->fiscal_code,
                    'birth_date' => $validated['birth_date'] ?? $user->birth_date,
                    'newsletter' => $validated['newsletter'] ?? $user->newsletter,
                ]);
            }

            // Associa l'utente al carrello
            $cart->update(['customer_id' => $user->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Errore durante il salvataggio dei dati: ' . $e->getMessage()], 500);
        }
    }
}
