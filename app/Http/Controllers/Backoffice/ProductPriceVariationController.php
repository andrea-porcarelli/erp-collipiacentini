<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPriceVariation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPriceVariationController extends Controller
{
    private function serialize(ProductPriceVariation $v): array
    {
        return [
            'id'              => $v->id,
            'date_from'       => $v->date_from->locale('it')->isoFormat('D MMMM YYYY'),
            'date_from_iso'   => $v->date_from->format('Y-m-d'),
            'date_to'         => $v->date_to->locale('it')->isoFormat('D MMMM YYYY'),
            'date_to_iso'     => $v->date_to->format('Y-m-d'),
            'direction'       => $v->direction,
            'direction_label' => $v->direction_label,
            'value'           => $v->value,
            'unit'            => $v->unit,
            'unit_label'      => $v->unit_label,
        ];
    }

    public function index(Product $product): JsonResponse
    {
        $variations = $product->priceVariations()
            ->orderBy('date_from')
            ->get()
            ->map(fn($v) => $this->serialize($v));

        return response()->json(['data' => $variations]);
    }

    private function checkOverlap(Product $product, string $dateFrom, string $dateTo, ?int $excludeId = null): bool
    {
        return $product->priceVariations()
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where('date_from', '<=', $dateTo)
            ->where('date_to', '>=', $dateFrom)
            ->exists();
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'direction' => 'required|in:increment,decrement',
            'value'     => 'required|numeric|min:0',
            'unit'      => 'required|in:euro,percent',
        ]);

        if ($this->checkOverlap($product, $data['date_from'], $data['date_to'])) {
            return response()->json([
                'message' => 'Il periodo selezionato si sovrappone a una variazione già esistente.',
            ], 422);
        }

        $variation = $product->priceVariations()->create($data);

        return response()->json($this->serialize($variation), 201);
    }

    public function update(Request $request, Product $product, ProductPriceVariation $variation): JsonResponse
    {
        abort_if($variation->product_id !== $product->id, 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'direction' => 'required|in:increment,decrement',
            'value'     => 'required|numeric|min:0',
            'unit'      => 'required|in:euro,percent',
        ]);

        if ($this->checkOverlap($product, $data['date_from'], $data['date_to'], $variation->id)) {
            return response()->json([
                'message' => 'Il periodo selezionato si sovrappone a una variazione già esistente.',
            ], 422);
        }

        $variation->update($data);

        return response()->json($this->serialize($variation));
    }

    public function destroy(Product $product, ProductPriceVariation $variation): JsonResponse
    {
        abort_if($variation->product_id !== $product->id, 403);
        $variation->delete();

        return response()->json(['ok' => true]);
    }
}
