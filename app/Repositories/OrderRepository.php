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
            ->when(! empty($filters['label']), function($q) use($filters) {
                $q->whereHas('orderProducts.product', function($q) use($filters) {
                    $q->where('label', 'like', '%' . $filters['label'] . '%');
                });
            })
            ->when(! empty($filters['order_number']), function($q) use($filters) {
                $q->where('order_number', 'like', '%' . $filters['order_number'] . '%');
            })
            ->when(! empty($filters['customer']), function($q) use($filters) {
                $term = $filters['customer'];
                $q->whereHas('customer', function($q) use($term) {
                    $q->where(function($q) use($term) {
                        $q->where('name', 'like', '%' . $term . '%')
                          ->orWhere('surname', 'like', '%' . $term . '%')
                          ->orWhereRaw("CONCAT(name, ' ', surname) LIKE ?", ['%' . $term . '%'])
                          ->orWhere('email', 'like', '%' . $term . '%')
                          ->orWhere('phone', 'like', '%' . $term . '%');
                    });
                });
            })
            ->when(! empty($filters['dates']), function($q) use($filters) {
                $dates = explode('|', $filters['dates']);
                $q->whereBetween('created_at', [Utils::data_from_ita($dates[0]), Utils::data_from_ita($dates[1])]);
            })
            ->when(! empty($filters['types']), function($q) use($filters) {
                $types = collect(json_decode($filters['types'], true))->map(function($item) {
                    return $item['name'];
                })->toArray();
                if (count($types) > 0) {
                    $q->whereIn('order_status', $types);
                }
            });
    }
}
