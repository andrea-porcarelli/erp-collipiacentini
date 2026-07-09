<?php

namespace App\Http\Controllers\Backoffice\Requests\Orders;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'date'       => ['required', 'date_format:Y-m-d'],
            'time'       => ['required', 'date_format:H:i'],

            'items'                => ['required', 'array', 'min:1'],
            'items.*.variant_id'   => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],

            'customer'              => ['required', 'array'],
            'customer.id'           => ['nullable', 'integer', 'exists:customers,id'],
            'customer.name'         => ['required', 'string', 'max:255'],
            'customer.surname'      => ['required', 'string', 'max:255'],
            'customer.email'        => ['required', 'email', 'max:255'],
            'customer.prefix_phone' => ['nullable', 'string', 'max:10'],
            'customer.phone'        => ['nullable', 'string', 'max:30'],
            'customer.address'      => ['nullable', 'string', 'max:255'],
            'customer.city'         => ['nullable', 'string', 'max:120'],
            'customer.zip_code'     => ['nullable', 'string', 'max:20'],
            'customer.fiscal_code'  => ['nullable', 'string', 'max:32'],

            'order_status' => ['required', Rule::in([OrderStatus::PENDING->value, OrderStatus::PAID->value])],
            'send_email'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'partner_id.required'      => 'Seleziona il partner',
            'product_id.required'      => 'Seleziona il prodotto',
            'date.required'            => 'Seleziona la data',
            'time.required'            => 'Seleziona un orario',
            'items.required'           => 'Aggiungi almeno un biglietto',
            'customer.name.required'   => 'Il nome del cliente è obbligatorio',
            'customer.surname.required' => 'Il cognome del cliente è obbligatorio',
            'customer.email.required'  => 'L\'email del cliente è obbligatoria',
            'customer.email.email'     => 'L\'email del cliente non è valida',
            'order_status.required'    => 'Seleziona lo stato dell\'ordine',
        ];
    }
}
