<?php

namespace App\Http\Controllers\Backoffice\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'surname'      => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'prefix_phone' => ['nullable', 'string', 'max:10'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:120'],
            'zip_code'     => ['nullable', 'string', 'max:20'],
            'country_id'   => ['nullable', 'integer', 'exists:countries,id'],
            'fiscal_code'  => ['nullable', 'string', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Il nome è obbligatorio',
            'surname.required' => 'Il cognome è obbligatorio',
            'email.required'   => 'L\'email è obbligatoria',
            'email.email'      => 'Email non valida',
        ];
    }
}
