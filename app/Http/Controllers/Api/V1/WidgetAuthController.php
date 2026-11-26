<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\WidgetToken;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetAuthController extends Controller
{
    /**
     * Emette un token short-lived vincolato al prodotto richiesto.
     * Chiamato dal widget JS stand-alone prima di iniziare il flusso di checkout.
     */
    public function issueToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
        ]);

        $numericId = str_starts_with($data['product_id'], 'ERP-PROD-')
            ? (int) substr($data['product_id'], 9)
            : (int) $data['product_id'];

        $product = Product::find($numericId);
        if (! $product) {
            return response()->json(['error' => 'not_found', 'message' => 'Prodotto non trovato'], 404);
        }

        $token = WidgetToken::issue($product);

        return response()->json([
            'widget_token' => $token,
            'expires_in_minutes' => WidgetToken::TTL_MINUTES,
        ]);
    }
}
