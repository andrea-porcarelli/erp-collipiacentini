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
                'label' => ['required', 'string', 'max:255', Rule::unique('products', 'label')->ignore($productId)],
                'is_active' => ['nullable', Rule::in(['0', '1'])],
            ],
            'ecommerce' => [
                'short_title' => ['required', 'string', 'max:55'],
                'short_description' => ['required', 'string', 'max:110'],
                'long_title' => ['required', 'string', 'max:108'],
            ],
            'duration' => [
                'duration_days' => ['nullable', 'integer', 'min:0'],
                'duration_hours' => ['nullable', 'integer', 'min:0', 'max:23'],
                'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
                'booking_deadline_hours' => ['nullable', 'integer', 'min:0', 'max:65535'],
            ],
            'occupancy' => [
                'occupancy' => ['required', 'integer', 'min:1'],
                'occupancy_for_price' => ['nullable'],
                'free_occupancy_rule' => ['nullable'],
                'max_tickets_per_session' => ['nullable', 'integer', 'min:1', 'max:65535'],
            ],
            'categories' => [
                'category_id' => ['nullable', 'exists:categories,id'],
            ],
            'public' => [
                'title' => ['nullable', 'string', 'max:255'],
                'meta_title' => ['required', 'string', 'max:255'],
                'meta_description' => ['nullable', 'string'],
                'meta_keywords' => ['nullable', 'string', 'max:500'],
            ],
            'visit' => [
                'visit_info' => ['nullable', 'string', 'max:600'],
                'support_email' => ['nullable', 'email', 'max:255'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Il nome prodotto interno è obbligatorio',
            'label.unique' => 'Il nome prodotto scelto è già stato usato',
            'duration_hours.max' => 'Le ore non possono superare 23',
            'duration_minutes.max' => 'I minuti non possono superare 59',
            'short_title.required' => 'Il nome breve è obbligatorio',
            'short_title.max' => 'Il nome breve non può superare 55 caratteri',
            'short_description.required' => 'La descrizione breve è obbligatoria',
            'short_description.max' => 'La descrizione breve non può superare 110 caratteri',
            'long_title.required' => 'Il nome completo è obbligatorio',
            'long_title.max' => 'Il nome completo non può superare 108 caratteri',
            'meta_title.required' => 'Il nome prodotto pubblico è obbligatorio',
            'category_id.exists' => 'La categoria selezionata non è valida',
            'visit_info.max' => 'Le informazioni sulla visita non possono superare 600 caratteri',
            'support_email.email' => 'Inserisci un indirizzo email valido',
        ];
    }
}
