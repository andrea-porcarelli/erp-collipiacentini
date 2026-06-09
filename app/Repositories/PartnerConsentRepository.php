<?php

namespace App\Repositories;

use App\Interfaces\PartnerConsentInterface;
use App\Models\PartnerConsent;
use Illuminate\Database\Eloquent\Builder;

class PartnerConsentRepository extends CrudRepository implements PartnerConsentInterface
{
    public function __construct(PartnerConsent $model)
    {
        parent::__construct($model);
    }

    public function filters(array $filters): Builder
    {
        return $this->builder()
            ->when(isset($filters['partner_id']), fn($q) => $q->where('partner_id', $filters['partner_id']))
            ->orderBy('position');
    }
}
