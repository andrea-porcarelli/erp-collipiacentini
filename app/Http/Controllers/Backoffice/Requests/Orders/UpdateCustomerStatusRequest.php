<?php

namespace App\Http\Controllers\Backoffice\Requests\Orders;

use App\Enums\CustomerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class UpdateCustomerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'customer_status' => ['required', new Enum(CustomerStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_status.required' => 'Lo stato cliente è obbligatorio',
            'customer_status.Illuminate\\Validation\\Rules\\Enum' => 'Stato cliente non valido',
        ];
    }
}
