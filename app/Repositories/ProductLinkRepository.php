<?php

namespace App\Repositories;

use App\Interfaces\ProductLinkInterface;
use App\Models\ProductLink;
use Illuminate\Database\Eloquent\Builder;

class ProductLinkRepository extends CrudRepository implements ProductLinkInterface
{
    public function __construct(ProductLink $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(isset($filters['product_id']), fn($q) => $q->where('product_id', $filters['product_id']));
    }
}
