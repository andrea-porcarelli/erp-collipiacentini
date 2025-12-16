<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{

    public function index(Request $request) : View {
        $company = $request->company;
        $products = $company->active_partners->flatMap(function ($partner) {
            return $partner->active_products()->get()->map(function ($product) {
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

}
