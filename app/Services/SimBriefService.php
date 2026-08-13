<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimBriefService
{
    /**
     * Fetch a live flight plan from SimBrief XML/JSON API using username or User ID.
     *
     * @param string $usernameOrId Pilot's SimBrief Username or numeric User ID
     * @return array|null
     */
    public function fetchLiveOfp(string $usernameOrId): ?array
    {
        $usernameOrId = trim($usernameOrId);
        if (empty($usernameOrId)) {
            return null;
        }

        try {
            $paramKey = is_numeric($usernameOrId) ? 'userid' : 'username';
            $url = "https://www.simbrief.com/api/xml.fetcher.php";
            
            $response = Http::timeout(8)->get($url, [
                $paramKey => $usernameOrId,
                'json' => 1,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['general']) && !isset($json['fetch']['error'])) {
                    $json['is_simbrief_live'] = true;
                    return $json;
                }
            }
            
            Log::warning('SimBrief Live Fetch Failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::warning('SimBrief Live Fetch Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate or fetch a flight plan via SimBrief API / customized parameters.
     *
     * @param array $flightData Customized dispatch parameters
     * @param string|null $username Pilot's SimBrief Username or User ID
     * @return array
     */
    public function generateOrFetchOfp(array $flightData, ?string $username = null): array
    {
        if (!empty($username)) {
            $liveOfp = $this->fetchLiveOfp($username);
            if ($liveOfp) {
                return $liveOfp;
            }
        }

        // Generate full, structured OFP payload matching dispatch variables
        $pax = (int)($flightData['passengers'] ?? 170);
        $bags = (int)($flightData['hold_bags'] ?? 152);
        $zfw = (int)($flightData['estimated_zfw'] ?? 61626);

        $burnFuel = 4200;
        $taxiFuel = 250;
        $contingencyFuel = 350;
        $altnFuel = 1100;
        $reserveFuel = 1200;
        $rampFuel = $burnFuel + $taxiFuel + $contingencyFuel + $altnFuel + $reserveFuel; // 7,100 kg
        $tow = $zfw + $rampFuel - $taxiFuel; // 68,476 kg
        $ldw = $tow - $burnFuel; // 64,276 kg

        $origIcao = strtoupper($flightData['orig'] ?? 'LFSB');
        $destIcao = strtoupper($flightData['dest'] ?? 'EDDH');
        $altn1 = strtoupper($flightData['altn'] ?? 'EDDW');
        $altn2 = strtoupper($flightData['altn2'] ?? 'EDHL');

        return [
            'is_simbrief_live' => false,
            'general' => [
                'flight_number' => strtoupper($flightData['flight_number'] ?? 'DS1181'),
                'callsign' => strtoupper($flightData['callsign'] ?? 'EZS64HZ'),
                'aircraft_type' => strtoupper($flightData['type'] ?? 'A20N'),
                'registration' => strtoupper($flightData['reg'] ?? 'HB-AYE'),
                'origin' => $origIcao,
                'destination' => $destIcao,
                'alternate' => $altn1,
                'alternate2' => $altn2,
                'route' => $flightData['route'] ?? 'DIRECT',
                'initial_altitude' => !empty($flightData['fl']) ? 'FL' . $flightData['fl'] : 'FL350',
                'cost_index' => $flightData['ci'] ?? '4',
                'air_distance' => $flightData['distance'] ?? 374,
                'gc_distance' => $flightData['distance'] ?? 374,
                'est_time_enroute' => '01:30',
                'units' => 'KGS',
                'ofp_layout' => $flightData['planformat'] ?? 'LIDO',
                'release_time' => date('d M Y H:i \U\T\C'),
            ],
            'weights' => [
                'oew' => 42500,
                'pax_count' => $pax,
                'bag_count' => $bags,
                'pax_weight' => $pax * 84,
                'bag_weight' => $bags * 15,
                'est_zfw' => $zfw,
                'max_zfw' => 64300,
                'est_tow' => $tow,
                'max_tow' => 79000,
                'est_ldw' => $ldw,
                'max_ldw' => 67400,
                'payload' => ($pax * 84) + ($bags * 15),
            ],
            'fuel' => [
                'taxi' => $taxiFuel,
                'enroute_burn' => $burnFuel,
                'contingency' => $contingencyFuel,
                'alternate' => $altnFuel,
                'reserve' => $reserveFuel,
                'plan_ramp' => $rampFuel,
                'plan_takeoff' => $rampFuel - $taxiFuel,
                'plan_landing' => $ldw - $zfw,
            ],
            'weather' => [
                'orig_metar' => "{$origIcao} " . date('dHi') . "Z 24008KT 9999 FEW045 22/14 Q1018 NOSIG",
                'dest_metar' => "{$destIcao} " . date('dHi') . "Z 28012KT 9999 CAVOK 20/12 Q1016 NOSIG",
                'altn_metar' => "{$altn1} " . date('dHi') . "Z 26010KT 9999 SCT030 21/13 Q1017 NOSIG",
            ],
            'text' => [
                'ofp_html' => "OFP RELEASE " . strtoupper($flightData['callsign'] ?? 'EZS64HZ') . " {$origIcao}-{$destIcao}",
            ]
        ];
    }
}
