<?php

namespace App\Repositories;

use App\Facades\Utils;
use App\Interfaces\UserInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class UserRepository extends CrudRepository implements UserInterface
{

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->where('role', '!=', 'customer')
            ->when(isset($filters['name']), function($q) use($filters) {
                $q->where('name', 'like', '%' . $filters['name'] . '%');
            });
    }
}
