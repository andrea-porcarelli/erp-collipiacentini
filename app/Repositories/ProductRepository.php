<?php

namespace App\Repositories;

use App\Interfaces\ProductInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;

class ProductRepository extends CrudRepository implements ProductInterface
{

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(! empty($filters['label']), function($q) use($filters) {
                $q->where('label', 'like', '%' . $filters['label'] . '%');
            });
    }
}
