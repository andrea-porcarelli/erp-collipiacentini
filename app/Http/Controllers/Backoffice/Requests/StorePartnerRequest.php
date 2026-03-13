<?php

namespace App\Http\Controllers\Backoffice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePartnerRequest extends FormRequest
{
    public function authorize() : bool {
        return Auth::check();
    }

    public function rules() : array {
        return [
            'partner_name' => ['required', 'unique:partners,partner_name'],
        ];
    }

    public function messages() : array
    {
        return [
            'partner_name.required' => 'Il nome del partner è obbligatorio',
            'partner_name.unique' => 'Il nome del partner scelto è già stato usato',
        ];
    }
}
