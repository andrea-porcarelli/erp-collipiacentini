<?php

namespace App\Repositories;

use App\Facades\Utils;
use App\Interfaces\CategoryInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Category;

class CategoryRepository extends CrudRepository implements CategoryInterface
{

    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(isset($filters['label']), function($q) use($filters) {
                $q->whereHas('languages', function($q)  use($filters) {
                    $q->where('label', 'like', '%' . $filters['label']. '%')
                        ->whereHas('language', function($q) {
                            $q->where('iso_code', Utils::default_language());
                        });
                });
            });
    }
}
