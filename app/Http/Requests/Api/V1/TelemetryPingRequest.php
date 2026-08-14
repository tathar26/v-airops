<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TelemetryPingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flight_id' => ['required', 'integer', 'exists:acars_active_flights,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'altitude_ft' => ['required', 'numeric'],
            'ground_speed_kt' => ['required', 'numeric', 'min:0'],
            'indicated_airspeed_kt' => ['required', 'numeric', 'min:0'],
            'vertical_speed_fpm' => ['required', 'numeric'],
            'pitch_deg' => ['required', 'numeric'],
            'bank_deg' => ['required', 'numeric'],
            'heading_deg' => ['required', 'numeric', 'between:0,360'],
            'fuel_qty_kg' => ['required', 'numeric', 'min:0'],
            'flight_phase' => ['required', 'string', 'max:30'],
            'timestamp' => ['nullable', 'date'],
        ];
    }
}
