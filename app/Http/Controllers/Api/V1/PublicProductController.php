<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function __construct(private ProductAvailabilityService $service) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $product = Product::queryByIdentifier($id)
            ->with([
                'category', 'cover', 'gallery', 'partner.consents' => function ($q) {
                    $q->where('is_active', true)->current()->orderBy('position');
                },
                'customerFields.fieldType',
                'variants.prices',
            ])->first();

        if (! $product) {
            return response()->json(['error' => 'not_found', 'message' => 'Prodotto non trovato'], 404);
        }

        $date = $request->query('date');
        $priceVariation = $date ? $this->service->getApplicablePriceVariation($product, $date) : null;

        $variants = $product->variants
            ->filter(fn ($v) => is_null($v->availability_id) && is_null($v->special_schedule_id))
            ->map(function ($v) use ($priceVariation) {
                $base = (float) $v->full_price;
                $final = $priceVariation ? $this->service->applyPriceVariation($base, $priceVariation) : $base;

                return [
                    'id' => $v->id,
                    'label' => $v->label,
                    'description' => $v->description,
                    'base_price' => $base,
                    'price' => $final,
                    'max_quantity' => $v->effective_max_quantity,
                ];
            })
            ->values()
            ->all();

        $customerFields = $product->customerFields->map(fn ($f) => [
            'key' => $f->fieldType?->key,
            'label' => $f->fieldType?->label,
            'required' => (bool) $f->is_required,
        ])->filter(fn ($f) => $f['key'] !== null)->values()->all();

        $consents = ($product->partner?->consents ?? collect())
            ->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'version' => $c->version,
                'required' => (bool) $c->is_required,
            ])->values()->all();

        return response()->json([
            'id' => 'ERP-PROD-'.$product->id,
            'sku' => $product->product_code,
            'label' => $product->label,
            'short_title' => $product->contentField('short_title'),
            'long_title' => $product->contentField('long_title'),
            'short_description' => $product->contentField('short_description'),
            'long_description' => $product->contentField('long_description'),
            'duration_minutes' => $product->duration,
            'max_tickets_per_session' => $product->max_tickets_per_session,
            'invoice_required' => (bool) $product->invoice_required,
            'applied_price_variation_id' => $priceVariation?->id,
            'variants' => $variants,
            'customer_fields' => $customerFields,
            'consents' => $consents,
            'partner' => $product->partner ? [
                'id' => $product->partner->id,
                'name' => $product->partner->partner_name,
                'brand' => $product->partner->brand,
                'css_style' => $product->partner->css_style,
                'default_layout_mode' => $product->partner->default_layout_mode,
            ] : null,
        ]);
    }

}
