<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\Request;

class CartExists
{
    public function handle(Request $request, Closure $next)
    {
        $sessionId = session()->getId();
        $cart = Cart::where('session_id', $sessionId)->first();

        if (!$cart) {
            return redirect('/shop')->with('error', 'Il carrello è vuoto');
        }

        return $next($request);
    }
}
