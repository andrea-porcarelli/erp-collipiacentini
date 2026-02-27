<?php

namespace App\Repositories;

use App\Interfaces\ProductCustomerFieldInterface;
use App\Models\ProductCustomerField;
use Illuminate\Database\Eloquent\Builder;

class ProductCustomerFieldRepository extends CrudRepository implements ProductCustomerFieldInterface
{
    public function __construct(ProductCustomerField $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(isset($filters['product_id']), fn($q) => $q->where('product_id', $filters['product_id']));
    }
}
