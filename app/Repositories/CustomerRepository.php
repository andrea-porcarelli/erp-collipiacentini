<?php

namespace App\Repositories;

use App\Facades\Utils;
use App\Interfaces\CustomerInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Customer;

class CustomerRepository extends CrudRepository implements CustomerInterface
{

    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(! empty($filters['customer']), function($q) use($filters) {
                $term = $filters['customer'];
                $q->where(function($q) use($term) {
                    $q->where('surname', 'like', '%' . $term . '%')
                      ->orWhere('email', 'like', '%' . $term . '%')
                      ->orWhere('phone', 'like', '%' . $term . '%');
                });
            })
            ->when(! empty($filters['purchased']), function($q) use($filters) {
                $values = collect(json_decode($filters['purchased'], true))->pluck('name')->filter()->toArray();
                $wantsYes = in_array('yes', $values, true);
                $wantsNo = in_array('no', $values, true);
                if ($wantsYes && ! $wantsNo) {
                    $q->has('orders');
                } elseif ($wantsNo && ! $wantsYes) {
                    $q->doesntHave('orders');
                }
            })
            ->orderByDesc('id');
    }
}
