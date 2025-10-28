<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
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

}
