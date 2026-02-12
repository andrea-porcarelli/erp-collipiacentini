<?php

namespace App\Http\Controllers\Backoffice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends FormRequest
{
    public function authorize() : bool {
        return Auth::check();
    }

    public function rules() : array {
        $rules = [
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'role' => ['required', 'in:god,admin,operator,partner,company,customer'],
        ];

        if ($this->get('role') === 'partner') {
            $rules['partner_id'] = ['required', 'exists:partners,id'];
        }

        if ($this->get('role') === 'company') {
            $rules['company_id'] = ['required', 'exists:companies,id'];
        }

        return $rules;
    }

    public function messages() : array
    {
        return [
            'name.required' => 'Il nome è obbligatorio',
            'email.required' => 'L\'email è obbligatoria',
            'email.email' => 'Inserisci un indirizzo email valido',
            'email.unique' => 'Questa email è già stata usata',
            'password.required' => 'La password è obbligatoria',
            'password.min' => 'La password deve avere almeno 8 caratteri',
            'role.required' => 'Il ruolo è obbligatorio',
            'partner_id.required' => 'Devi selezionare un partner',
            'company_id.required' => 'Devi selezionare un\'azienda',
        ];
    }
}
