<?php

namespace App\Repositories;

use App\Facades\Utils;
use App\Interfaces\CompanyInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Company;

class CompanyRepository extends CrudRepository implements CompanyInterface
{

    public function __construct(Company $model)
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
