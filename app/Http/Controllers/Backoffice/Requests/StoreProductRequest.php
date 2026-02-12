<?php

namespace App\Http\Controllers\Backoffice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    public function authorize() : bool {
        return Auth::check();
    }

    public function rules() : array {
        $rules['label'] = ['required', 'unique:products,label'];

        if (in_array(Auth::user()->role, ['god', 'admin', 'company'])) {
            $rules['partner_id'] = ['required', 'exists:partners,id'];
        }

        return $rules;
    }

    public function messages() : array
    {
        return [
            'label.required' => 'Il nome del prodotto è obbligatorio',
            'label.unique' => 'Il nome del prodotto scelto è già stato usato',
            'partner_id.required' => 'Devi selezionare il partner',
        ];
    }
}
