<?php

namespace App\Http\Controllers\Backoffice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'label'         => ['required', 'string', 'max:255'],
            'category_code' => ['required', 'string', 'max:100'],
            'iva'           => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required'         => 'Il nome della categoria è obbligatorio',
            'category_code.required' => 'Il codice categoria è obbligatorio',
            'iva.numeric'            => 'L\'IVA deve essere un valore numerico',
        ];
    }
}
