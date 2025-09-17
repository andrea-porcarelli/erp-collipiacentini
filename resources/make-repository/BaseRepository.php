<?php

namespace App\Repositories;

use App\Facades\Utils;
use App\Interfaces\BaseInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Model;

class BaseRepository extends CrudRepository implements BaseInterface
{

    public function __construct(BaseModel $model)
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
