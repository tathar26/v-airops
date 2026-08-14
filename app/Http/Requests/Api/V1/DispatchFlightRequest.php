<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DispatchFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flight_number' => ['required', 'string', 'max:20'],
            'origin_icao' => ['required', 'string', 'size:4'],
            'destination_icao' => ['required', 'string', 'size:4'],
            'route' => ['nullable', 'string'],
            'aircraft_type' => ['nullable', 'string', 'max:20'],
            'planned_altitude' => ['nullable', 'integer'],
            'planned_fuel_kg' => ['nullable', 'numeric'],
            'planned_zfw_kg' => ['nullable', 'numeric'],
            'simbrief_ofp_id' => ['nullable', 'string', 'max:50'],
        ];
    }
}
