<?php

namespace App\Repositories;

use App\Facades\Utils;
use App\Interfaces\OrderInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Order;

class OrderRepository extends CrudRepository implements OrderInterface
{

    public function __construct(Order $model)
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
            })
            ->when(isset($filters['dates']), function($q) use($filters) {
                $dates = explode('|', $filters['dates']);
                $q->whereBetween('created_at', [Utils::data_from_ita($dates[0]), Utils::data_from_ita($dates[1])]);
            })
            ->when(isset($filters['types']), function($q) use($filters) {
                $types = collect(json_decode($filters['types'], true))->map(function($item) {
                    return $item['name'];
                })->toArray();
                if (count($types) > 0) {
                    $q->whereIn('order_status', $types);
                }
            });
    }
}
