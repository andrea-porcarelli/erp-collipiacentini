<?php

namespace App\Repositories;

use App\Interfaces\ProductFaqInterface;
use App\Models\ProductFaq;
use Illuminate\Database\Eloquent\Builder;

class ProductFaqRepository extends CrudRepository implements ProductFaqInterface
{
    public function __construct(ProductFaq $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(isset($filters['product_id']), fn($q) => $q->where('product_id', $filters['product_id']));
    }
}
