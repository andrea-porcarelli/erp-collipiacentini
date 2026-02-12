<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErpProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->get('company');
        $perPage = min((int) $request->query('per_page', 100), 500);
        $page = max((int) $request->query('page', 1), 1);

        $products = Product::whereHas('partner', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->with(['category', 'prices', 'contents.language', 'cover', 'gallery', 'availabilities', 'partner.company'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'products' => ErpProductResource::collection($products->items()),
            'pagination' => [
                'page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'total_pages' => $products->lastPage(),
            ],
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        $company = $request->get('company');

        $query = Product::whereHas('partner', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        });

        $total = $query->count();
        $lastUpdated = $query->max('updated_at');

        // Checksum based on ids and updated_at
        $checksumData = Product::whereHas('partner', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->orderBy('id')
            ->get(['id', 'updated_at'])
            ->map(fn($p) => $p->id . ':' . $p->updated_at)
            ->implode('|');

        return response()->json([
            'total_products' => $total,
            'last_updated' => $lastUpdated,
            'checksum' => md5($checksumData),
        ]);
    }
}
