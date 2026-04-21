<?php

namespace App\Traits;

use App\Services\ProductSeoService;

trait InvalidatesProductSeoCache
{
    protected static function bootInvalidatesProductSeoCache(): void
    {
        $flush = function ($model) {
            foreach ($model->productSeoCacheIds() as $id) {
                if ($id) {
                    ProductSeoService::forget((int) $id);
                }
            }
        };

        static::saved($flush);
        static::deleted($flush);
    }

    public function productSeoCacheIds(): array
    {
        return array_filter([$this->product_id ?? null]);
    }
}
