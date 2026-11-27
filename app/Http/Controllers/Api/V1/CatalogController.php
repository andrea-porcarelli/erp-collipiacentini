<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Product;
use App\Services\WordpressSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

    /**
     * Lista ridotta per il picker del plugin WordPress: tutti i prodotti attivi
     * del partner, meno quelli già mappati (array `exclude[]` di id canonici).
     * Per ogni prodotto include le prossime 3 date con disponibilità > 0.
     */
    public function available(Request $request): JsonResponse
    {
        $exclude = (array) $request->query('exclude', []);
        $excludeIds = array_filter(array_map(function ($id) {
            if (is_string($id) && str_starts_with($id, 'ERP-PROD-')) {
                return (int) substr($id, 9);
            }
            return ctype_digit((string) $id) ? (int) $id : null;
        }, $exclude));

        $products = $this->scopedQuery($request)
            ->with(['contents.language', 'availabilities', 'specialSchedules', 'closedPeriods'])
            ->where('is_active', true)
            ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderBy('label')
            ->get();

        $items = $products->map(function (Product $p) {
            $name = $p->contentField('long_title') ?? $p->contentField('short_title') ?? $p->label;
            return [
                'id'         => 'ERP-PROD-'.$p->id,
                'sku'        => $p->product_code,
                'name'       => $name,
                'price'      => (float) $p->lowest_price,
                'next_dates' => $this->nextAvailableDates($p, 3),
            ];
        })->values();

        return response()->json([
            'products' => $items,
            'total'    => $items->count(),
        ]);
    }

    /**
     * Prossime N date disponibili per un prodotto, combinando:
     *  - product_special_schedules (date puntuali non disabilitate)
     *  - product_availabilities    (ricorrenza settimanale su day_of_week)
     * Escludendo quelle che cadono in un closedPeriod.
     */
    private function nextAvailableDates(Product $product, int $limit = 3, int $horizonDays = 90): array
    {
        $today = Carbon::today();
        $end   = $today->copy()->addDays($horizonDays);

        $weeklyDays = $product->availabilities
            ->whereNotNull('day_of_week')
            ->pluck('day_of_week')
            ->unique()
            ->map(fn ($d) => (int) $d)
            ->all();

        $todayStr = $today->toDateString();
        $endStr   = $end->toDateString();
        $specials = $product->specialSchedules
            ->filter(function ($s) use ($todayStr, $endStr) {
                if ($s->is_disabled || ! $s->date) return false;
                $d = $s->date->toDateString();
                return $d >= $todayStr && $d <= $endStr;
            })
            ->map(fn ($s) => $s->date->toDateString());

        $closedRanges = $product->closedPeriods
            ->map(fn ($p) => [$p->date_from?->toDateString(), $p->date_to?->toDateString()])
            ->filter(fn ($r) => $r[0] && $r[1])
            ->all();
        $isClosed = function (string $d) use ($closedRanges): bool {
            foreach ($closedRanges as [$from, $to]) {
                if ($d >= $from && $d <= $to) return true;
            }
            return false;
        };

        $dates = collect($specials);
        if (! empty($weeklyDays)) {
            $cur = $today->copy();
            $cap = $limit * 5;
            while ($cur->lte($end) && $dates->count() < $cap) {
                if (in_array($cur->isoWeekday(), $weeklyDays, true)) {
                    $dates->push($cur->toDateString());
                }
                $cur->addDay();
            }
        }

        return $dates
            ->unique()
            ->reject($isClosed)
            ->sort()
            ->take($limit)
            ->values()
            ->all();
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
