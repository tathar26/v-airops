<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AcarsActiveFlight;
use Illuminate\Foundation\Http\FormRequest;

class TelemetryPingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        // 1. Resolve flight_id if missing or string
        if ((!isset($input['flight_id']) || empty($input['flight_id'])) && $this->user()) {
            $activeFlight = AcarsActiveFlight::where('user_id', $this->user()->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();
            if ($activeFlight) {
                $input['flight_id'] = $activeFlight->id;
            }
        }

        // 2. Normalize coordinate aliases
        if (!isset($input['latitude']) && isset($input['lat'])) {
            $input['latitude'] = $input['lat'];
        }
        if (!isset($input['longitude']) && (isset($input['lon']) || isset($input['lng']))) {
            $input['longitude'] = $input['lon'] ?? $input['lng'];
        }

        // 3. Normalize altitude
        if (!isset($input['altitude_ft'])) {
            $input['altitude_ft'] = $input['altitude'] ?? ($input['alt'] ?? 0);
        }

        // 4. Normalize speed aliases (ground_speed_kt)
        if (!isset($input['ground_speed_kt'])) {
            $input['ground_speed_kt'] = $input['groundspeed'] ?? ($input['ground_speed'] ?? ($input['speed'] ?? ($input['gs'] ?? 0)));
        }

        // 5. Normalize airspeed (indicated_airspeed_kt)
        if (!isset($input['indicated_airspeed_kt'])) {
            $input['indicated_airspeed_kt'] = $input['indicated_speed'] ?? ($input['ias'] ?? ($input['ground_speed_kt'] ?? 0));
        }

        // 6. Normalize vertical speed (vertical_speed_fpm)
        if (!isset($input['vertical_speed_fpm'])) {
            $input['vertical_speed_fpm'] = $input['vertical_speed'] ?? ($input['vs'] ?? ($input['vspeed'] ?? 0));
        }

        // 7. Normalize heading (heading_deg)
        if (!isset($input['heading_deg'])) {
            $input['heading_deg'] = $input['heading'] ?? ($input['hdg'] ?? 0);
        }
        if (isset($input['heading_deg']) && is_numeric($input['heading_deg'])) {
            $input['heading_deg'] = fmod((float)$input['heading_deg'] + 360.0, 360.0);
        }

        // 8. Normalize pitch & bank
        if (!isset($input['pitch_deg'])) {
            $input['pitch_deg'] = $input['pitch'] ?? 0;
        }
        if (!isset($input['bank_deg'])) {
            $input['bank_deg'] = $input['bank'] ?? ($input['roll'] ?? 0);
        }

        // 9. Normalize fuel
        if (!isset($input['fuel_qty_kg'])) {
            $input['fuel_qty_kg'] = $input['fuel_kg'] ?? ($input['fuel'] ?? ($input['fuel_quantity'] ?? 0));
        }

        // 10. Normalize flight phase
        if (!isset($input['flight_phase'])) {
            $input['flight_phase'] = $input['phase'] ?? ($input['status'] ?? 'CRUISING');
        }

        $this->replace($input);
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
            'flight_phase' => ['required', 'string', 'max:50'],
            'timestamp' => ['nullable', 'date'],
        ];
    }
}
