<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Autentica le chiamate API del widget di prenotazione embeddabile.
 * Il token è short-lived (30 min), emesso dalla rotta /widget/booking/{code}
 * e persistito in cache (non DB) perché scade da solo e non deve sopravvivere a restart.
 */
class WidgetToken
{
    public const CACHE_PREFIX = 'widget_token:';

    public const TTL_MINUTES = 30;

    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('x-miticko-widget-token') ?? $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'token_mancante', 'message' => 'Header X-Miticko-Widget-Token richiesto'], 401);
        }

        $payload = Cache::get(self::CACHE_PREFIX.$token);

        if (! $payload || ! isset($payload['partner_id'], $payload['product_id'])) {
            return response()->json(['error' => 'token_invalido', 'message' => 'Token scaduto o non valido'], 401);
        }

        $partner = Partner::find($payload['partner_id']);
        $product = Product::find($payload['product_id']);

        if (! $partner || ! $product) {
            return response()->json(['error' => 'not_found', 'message' => 'Partner o prodotto non trovati'], 404);
        }

        $request->merge([
            'partner' => $partner,
            'widget_product' => $product,
            'widget_token' => $token,
        ]);

        return $next($request);
    }

    public static function issue(Product $product): string
    {
        $token = \Illuminate\Support\Str::random(64);
        Cache::put(self::CACHE_PREFIX.$token, [
            'partner_id' => $product->partner_id,
            'product_id' => $product->id,
            'issued_at' => now()->toIso8601String(),
        ], now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }
}
