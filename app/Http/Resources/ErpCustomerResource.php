<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $company = $request->get('company');

        return [
            'id' => 'ERP-CUST-' . $this->id,
            'email' => $this->email,
            'first_name' => $this->name,
            'last_name' => $this->surname,
            'phone' => trim(($this->prefix_phone ?? '') . ' ' . ($this->phone ?? '')),
            'company' => $company?->company_name,
            'billing_address' => [
                'address_1' => $this->address,
                'city' => $this->city,
                'postcode' => $this->zip_code,
                'country' => $this->country?->iso_code ?? $this->country?->code ?? null,
            ],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
