<?php

namespace App\Repositories;

use App\Interfaces\ProductRelatedInterface;
use App\Models\ProductRelated;
use Illuminate\Database\Eloquent\Builder;

class ProductRelatedRepository extends CrudRepository implements ProductRelatedInterface
{
    public function __construct(ProductRelated $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(isset($filters['product_id']), fn($q) => $q->where('product_id', $filters['product_id']));
    }
}
