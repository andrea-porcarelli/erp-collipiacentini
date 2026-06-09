<?php

namespace App\Http\Controllers\Backoffice\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'booking_date' => ['required', 'date'],
            'booking_time' => ['required', 'date_format:H:i'],
            'slot_type'    => ['nullable', 'in:weekly,special'],
            'slot_id'      => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_date.required'    => 'La data della visita è obbligatoria',
            'booking_date.date'        => 'Data non valida',
            'booking_time.required'    => 'L\'orario della visita è obbligatorio',
            'booking_time.date_format' => 'Orario non valido (formato HH:MM)',
        ];
    }
}
