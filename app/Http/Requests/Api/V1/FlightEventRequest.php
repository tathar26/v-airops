<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class FlightEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flight_id' => ['required', 'integer', 'exists:acars_active_flights,id'],
            'event_type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'in:INFO,WARNING,CRITICAL'],
            'penalty_points' => ['nullable', 'integer', 'min:0'],
            'telemetry_snapshot' => ['nullable', 'array'],
        ];
    }
}
