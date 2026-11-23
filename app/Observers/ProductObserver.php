<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\WordpressSyncService;

class ProductObserver
{
    public function __construct(private WordpressSyncService $sync) {}

    public function saved(Product $product): void
    {
        $this->sync->dispatchProductUpsert($product);
    }

    public function deleted(Product $product): void
    {
        $this->sync->dispatchProductDeleted($product);
    }
}
