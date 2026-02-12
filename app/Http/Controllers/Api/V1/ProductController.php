<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErpProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, string $id): JsonResponse
    {
        $company = $request->get('company');
        $numericId = $this->extractNumericId($id);

        $product = Product::whereHas('partner', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->with(['category', 'prices', 'contents.language', 'cover', 'gallery', 'availabilities', 'partner.company'])
            ->find($numericId);

        if (!$product) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Prodotto non trovato',
            ], 404);
        }

        return response()->json(new ErpProductResource($product));
    }

    public function batch(Request $request): JsonResponse
    {
        $company = $request->get('company');
        $ids = $request->input('ids', []);

        if (count($ids) > 100) {
            return response()->json([
                'error' => 'validation_error',
                'message' => 'Massimo 100 prodotti per richiesta',
            ], 422);
        }

        $numericIds = array_map(fn($id) => $this->extractNumericId($id), $ids);

        $products = Product::whereHas('partner', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->with(['category', 'prices', 'contents.language', 'cover', 'gallery', 'availabilities', 'partner.company'])
            ->whereIn('id', $numericIds)
            ->get();

        $foundIds = $products->pluck('id')->toArray();
        $notFound = [];
        foreach ($ids as $i => $originalId) {
            if (!in_array($numericIds[$i], $foundIds)) {
                $notFound[] = $originalId;
            }
        }

        return response()->json([
            'products' => ErpProductResource::collection($products),
            'not_found' => $notFound,
        ]);
    }

    private function extractNumericId(string $id): int
    {
        if (str_starts_with($id, 'ERP-PROD-')) {
            return (int) substr($id, 9);
        }

        return (int) $id;
    }
}
