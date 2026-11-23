<?php

namespace App\Services;

use App\Jobs\SendPartnerWebhook;
use App\Models\Partner;
use App\Models\Product;

class WordpressSyncService
{
    public function productPayload(Product $product, string $status = null): array
    {
        $product->loadMissing(['category', 'cover', 'gallery', 'partner']);

        $images = [];
        foreach ($product->cover as $m) {
            $images[] = ['url' => asset('storage/'.$m->file_path), 'alt' => $m->description ?? $product->label];
        }
        foreach ($product->gallery as $m) {
            $images[] = ['url' => asset('storage/'.$m->file_path), 'alt' => $m->description ?? $product->label];
        }

        return [
            'id' => 'ERP-PROD-'.$product->id,
            'sku' => $product->product_code,
            'status' => $status ?? ($product->is_active ? 'active' : 'inactive'),
            'public_url' => $product->public_url,
            'lowest_price' => (float) $product->lowest_price,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'label' => $product->category->label,
            ] : null,
            'images' => $images,
            'content' => [
                'short_title' => $product->contentField('short_title'),
                'long_title' => $product->contentField('long_title'),
                'short_description' => $product->contentField('short_description'),
                'long_description' => $product->contentField('long_description'),
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
            ],
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }

    public function dispatchProductUpsert(Product $product): void
    {
        $partner = $product->partner;

        if (! $partner || ! $this->partnerIsReady($partner)) {
            return;
        }

        $event = $product->is_active ? 'product.upserted' : 'product.hidden';

        SendPartnerWebhook::dispatch(
            partnerId: $partner->id,
            event: $event,
            payload: $this->productPayload($product),
        );
    }

    public function dispatchProductDeleted(Product $product): void
    {
        $partner = $product->partner;

        if (! $partner || ! $this->partnerIsReady($partner)) {
            return;
        }

        SendPartnerWebhook::dispatch(
            partnerId: $partner->id,
            event: 'product.deleted',
            payload: ['id' => 'ERP-PROD-'.$product->id, 'sku' => $product->product_code],
        );
    }

    public function triggerFullSync(Partner $partner): int
    {
        if (! $this->partnerIsReady($partner)) {
            return 0;
        }

        $products = Product::where('partner_id', $partner->id)
            ->where('is_active', true)
            ->get();

        foreach ($products as $product) {
            SendPartnerWebhook::dispatch(
                partnerId: $partner->id,
                event: 'product.upserted',
                payload: $this->productPayload($product),
            );
        }

        return $products->count();
    }

    private function partnerIsReady(Partner $partner): bool
    {
        return (bool) $partner->wordpress_endpoint && (bool) $partner->wordpress_webhook_secret;
    }
}
