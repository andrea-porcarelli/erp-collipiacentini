<?php

namespace App\Http\Controllers\Backoffice\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'customer_note' => ['nullable', 'string', 'max:5000'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
