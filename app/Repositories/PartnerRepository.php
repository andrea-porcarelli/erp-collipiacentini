<?php

namespace App\Repositories;

use App\Facades\Utils;
use App\Interfaces\PartnerInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Partner;

class PartnerRepository extends CrudRepository implements PartnerInterface
{

    public function __construct(Partner $model)
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
