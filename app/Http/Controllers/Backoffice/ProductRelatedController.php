<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\ProductRelatedInterface;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRelatedController extends CrudController
{
    const MAX_RELATED = 5;

    public ProductRelatedInterface $interface;
    public string $path = 'product-related';

    public function __construct(ProductRelatedInterface $interface)
    {
        $this->interface = $interface;
    }

    public function index(int $productId): JsonResponse
    {
        try {
            $items = $this->interface->filters(['product_id' => $productId])
                ->with('relatedProduct')
                ->get()
                ->map(fn($r) => [
                    'id'                  => $r->id,
                    'related_product_id'  => $r->related_product_id,
                    'label'               => $r->relatedProduct?->label,
                    'product_code'        => $r->relatedProduct?->product_code,
                ]);

            return $this->success(['data' => $items]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        try {
            $request->validate([
                'related_product_id' => 'required|integer|exists:products,id',
            ]);

            $relatedProductId = (int) $request->input('related_product_id');

            if ($relatedProductId === $productId) {
                return $this->error(['message' => 'Non puoi collegare un prodotto a se stesso.']);
            }

            $count = $this->interface->filters(['product_id' => $productId])->count();
            if ($count >= self::MAX_RELATED) {
                return $this->error(['message' => 'Puoi collegare al massimo ' . self::MAX_RELATED . ' prodotti correlati.']);
            }

            $existing = $this->interface->filters(['product_id' => $productId])
                ->where('related_product_id', $relatedProductId)
                ->exists();

            if ($existing) {
                return $this->error(['message' => 'Questo prodotto è già collegato.']);
            }

            $related = $this->interface->store([
                'product_id'         => $productId,
                'related_product_id' => $relatedProductId,
            ]);

            $related->load('relatedProduct');

            return $this->success([
                'id'                  => $related->id,
                'related_product_id'  => $related->related_product_id,
                'label'               => $related->relatedProduct?->label,
                'product_code'        => $related->relatedProduct?->product_code,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function destroy(int $productId, int $relatedId): JsonResponse
    {
        try {
            $this->interface->remove($relatedId);
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function find(Request $request, int $productId): JsonResponse
    {
        try {
            $q = $request->input('q', '');

            $alreadyLinked = $this->interface->filters(['product_id' => $productId])
                ->pluck('related_product_id')
                ->toArray();

            $products = Product::where('id', '!=', $productId)
                ->whereNotIn('id', $alreadyLinked)
                ->where(function ($query) use ($q) {
                    $query->where('label', 'like', "%{$q}%");
                })
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'id'           => $p->id,
                    'label'        => $p->label,
                    'product_code' => $p->product_code,
                ]);

            return $this->success(['data' => $products]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
