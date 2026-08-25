<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PirepSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flight_id' => ['required', 'integer', 'exists:acars_active_flights,id'],
            'block_off_time' => ['nullable', 'date'],
            'block_on_time' => ['nullable', 'date'],
            'block_time_minutes' => ['required', 'integer', 'min:0'],
            'fuel_used_kg' => ['nullable', 'numeric', 'min:0'],
            'touchdown_fpm' => ['required', 'numeric'],
            'touchdown_gforce' => ['nullable', 'numeric'],
            'aircraft_title' => ['nullable', 'string', 'max:255'],
            'livery' => ['nullable', 'string', 'max:255'],
            'atc_model' => ['nullable', 'string', 'max:50'],
            'aircraft_type' => ['nullable', 'string', 'max:50'],
            'simulator' => ['nullable', 'string', 'max:100'],
            'events' => ['nullable', 'array'],
        ];
    }
}
