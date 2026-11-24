<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Product;
use App\Services\WordpressSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request, WordpressSyncService $sync): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 100), 500);
        $page = max((int) $request->query('page', 1), 1);

        $query = $this->scopedQuery($request)
            ->with(['category', 'variants.prices', 'cover', 'gallery', 'partner'])
            ->where('is_active', true);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn (Product $p) => $sync->productPayload($p))
            ->all();

        return response()->json([
            'products' => $items,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        $query = $this->scopedQuery($request)->where('is_active', true);

        $total = (clone $query)->count();
        $lastUpdated = (clone $query)->max('updated_at');

        $checksumData = (clone $query)
            ->orderBy('id')
            ->get(['id', 'updated_at'])
            ->map(fn ($p) => $p->id.':'.$p->updated_at)
            ->implode('|');

        return response()->json([
            'total_products' => $total,
            'last_updated' => $lastUpdated,
            'checksum' => md5($checksumData),
        ]);
    }

    private function scopedQuery(Request $request)
    {
        $partner = $request->get('partner');

        if ($partner instanceof Partner) {
            return Product::where('partner_id', $partner->id);
        }

        return Product::query();
    }
}
