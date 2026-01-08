<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

}
