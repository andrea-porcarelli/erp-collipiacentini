<?php

namespace App\Http\Controllers\Backoffice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCompanyRequest extends FormRequest
{
    public function authorize() : bool {
        return Auth::check();
    }

    public function rules() : array {
        return [
            'company_name' => ['required', 'unique:companies,company_name'],
            'vat_number' => ['required', 'unique:companies,vat_number'],
        ];
    }

    public function messages() : array
    {
        return [
            'company_name.required' => 'Il nome dell\'azienda è obbligatorio',
            'company_name.unique' => 'Il nome dell\'azienda scelto è già stato usato',
            'vat_number.required' => 'La partita IVA è obbligatoria',
            'vat_number.unique' => 'La partita IVA inserita è già stata usata',
        ];
    }
}
