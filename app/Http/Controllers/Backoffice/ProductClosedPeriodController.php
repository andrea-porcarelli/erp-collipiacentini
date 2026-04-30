<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductClosedPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductClosedPeriodController extends Controller
{
    private function authorizeAccess(Product $product): void
    {
        $user = Auth::user();
        if (in_array($user->role, ['god', 'admin'])) {
            return;
        }
        if ($user->role === 'partner' && $product->partner_id !== $user->partner_id) {
            abort(403);
        }
    }

    private function serialize(ProductClosedPeriod $p): array
    {
        return [
            'id'           => $p->id,
            'date_from'    => $p->date_from->locale('it')->isoFormat('D MMMM YYYY'),
            'date_to'      => $p->date_to->locale('it')->isoFormat('D MMMM YYYY'),
            'date_from_iso' => $p->date_from->format('Y-m-d'),
            'date_to_iso'   => $p->date_to->format('Y-m-d'),
        ];
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($product);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        $period = $product->closedPeriods()->create($data);

        return response()->json($this->serialize($period));
    }

    public function destroy(Request $request, Product $product, ProductClosedPeriod $period): JsonResponse
    {
        try {
            abort_if((int) $period->product_id !== (int) $product->id, 403);
            $this->authorizeAccess($product);

            $period->delete();

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }
}
