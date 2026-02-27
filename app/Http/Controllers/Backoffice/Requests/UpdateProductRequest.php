<?php

namespace App\Http\Controllers\Backoffice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return match ($this->input('section')) {
            'settings' => [
                'label'     => ['required', 'string', 'max:255', Rule::unique('products', 'label')->ignore($productId)],
                'is_active' => ['nullable', Rule::in(['0', '1'])],
            ],
            'duration' => [
                'duration_days'    => ['nullable', 'integer', 'min:0'],
                'duration_hours'   => ['nullable', 'integer', 'min:0', 'max:23'],
                'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
            ],
            'categories' => [
                'category_id' => ['nullable', 'exists:categories,id'],
            ],
            'public' => [
                'meta_title'       => ['required', 'string', 'max:255'],
                'meta_description' => ['nullable', 'string'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'label.required'          => 'Il nome prodotto interno è obbligatorio',
            'label.unique'            => 'Il nome prodotto scelto è già stato usato',
            'duration_hours.max'      => 'Le ore non possono superare 23',
            'duration_minutes.max'    => 'I minuti non possono superare 59',
            'meta_title.required'     => 'Il nome prodotto pubblico è obbligatorio',
            'category_id.exists'      => 'La categoria selezionata non è valida',
        ];
    }
}
