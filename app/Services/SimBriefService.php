<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SimBriefService
{
    /**
     * Get available airframe profiles from SimBrief API matching an aircraft type code.
     *
     * @param string $typeCode Aircraft ICAO code (e.g. A320, A20N, B738)
     * @return array
     */
    public function getAirframesForType(string $typeCode): array
    {
        $typeCode = strtoupper(trim($typeCode));
        if (empty($typeCode)) {
            return [];
        }

        $allAirframes = Cache::remember('simbrief_all_airframes_v1', 86400, function () {
            try {
                $response = Http::timeout(10)->get('https://www.simbrief.com/api/inputs.airframes.json');
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch SimBrief airframes: ' . $e->getMessage());
            }
            return null;
        });

        if (!is_array($allAirframes)) {
            return [];
        }

        // Match type code and common family aliases
        $candidateTypes = [$typeCode];
        if (in_array($typeCode, ['A320', 'A20N'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['A320', 'A20N']));
        } elseif (in_array($typeCode, ['A321', 'A21N'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['A321', 'A21N']));
        } elseif (in_array($typeCode, ['A319', 'A19N'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['A319', 'A19N']));
        } elseif (in_array($typeCode, ['B738', 'B38M'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['B738', 'B38M']));
        } elseif (in_array($typeCode, ['B737', 'B736', 'B739'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['B737', 'B736', 'B739']));
        } elseif (in_array($typeCode, ['B777', 'B77W', 'B772', 'B77L', 'B77F'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['B77W', 'B772', 'B77L', 'B77F', 'B777']));
        } elseif (in_array($typeCode, ['B787', 'B788', 'B789', 'B78X'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['B789', 'B788', 'B78X', 'B787']));
        } elseif (in_array($typeCode, ['A330', 'A339', 'A333', 'A332'])) {
            $candidateTypes = array_unique(array_merge([$typeCode], ['A339', 'A333', 'A332', 'A330']));
        }

        $results = [];
        foreach ($candidateTypes as $type) {
            if (!empty($allAirframes[$type]['airframes']) && is_array($allAirframes[$type]['airframes'])) {
                foreach ($allAirframes[$type]['airframes'] as $af) {
                    $internalId = (string)($af['airframe_internal_id'] ?? ($af['airframe_id'] ?? ''));
                    if (empty($internalId)) {
                        continue;
                    }
                    $comments = trim((string)($af['airframe_comments'] ?? ''));
                    $name = trim((string)($af['airframe_name'] ?? ''));
                    $displayName = $comments ?: ($name ?: $internalId);
                    if (strtolower($displayName) === 'default') {
                        $displayName = 'SimBrief Standard (' . $type . ')';
                    }

                    $maxPax = (int)($af['airframe_passengers'] ?? ($af['airframe_options']['maxpax'] ?? 180));
                    $oew = (int)($af['airframe_options']['oew'] ?? 42500);

                    $results[$internalId] = [
                        'id' => $internalId,
                        'type' => $internalId,
                        'name' => $displayName,
                        'max_pax' => $maxPax,
                        'max_bags' => (int)($maxPax * 1.1),
                        'oew' => $oew,
                        'base_type' => $type,
                    ];
                }
            }
        }

        return $results;
    }
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
            
            $response = Http::timeout(10)->get($url, [
                $paramKey => $usernameOrId,
                'json' => 'v2',
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if ((isset($json['general']) || isset($json['origin'])) && !isset($json['fetch']['error'])) {
                    $json['is_simbrief_live'] = true;

                    // Ensure 'general' array exists
                    if (!isset($json['general'])) {
                        $json['general'] = [];
                    }

                    // Normalize SimBrief JSON v2 keys into 'general' for seamless consumption
                    if (empty($json['general']['origin']) && isset($json['origin']['icao_code'])) {
                        $json['general']['origin'] = $json['origin']['icao_code'];
                    }
                    if (empty($json['general']['destination']) && isset($json['destination']['icao_code'])) {
                        $json['general']['destination'] = $json['destination']['icao_code'];
                    }
                    if (empty($json['general']['callsign']) && isset($json['atc']['callsign'])) {
                        $json['general']['callsign'] = $json['atc']['callsign'];
                    }
                    if (empty($json['general']['flight_number']) && isset($json['general']['flight_number'])) {
                        $json['general']['flight_number'] = $json['general']['flight_number'];
                    }
                    if (empty($json['general']['alternate']) && isset($json['alternate']['icao_code'])) {
                        $json['general']['alternate'] = $json['alternate']['icao_code'];
                    }
                    if (empty($json['general']['route']) && isset($json['general']['route'])) {
                        $json['general']['route'] = $json['general']['route'];
                    }
                    if (empty($json['general']['ofp_layout'])) {
                        $json['general']['ofp_layout'] = strtoupper((string)($json['params']['planformat'] ?? ($json['params']['plan_format'] ?? ($json['ofp_layout'] ?? ($json['general']['layout'] ?? 'LIDO')))));
                    }
                    if (empty($json['general']['aircraft_type']) && isset($json['aircraft']['icaocode'])) {
                        $json['general']['aircraft_type'] = $json['aircraft']['icaocode'];
                    }
                    if (empty($json['general']['registration']) && isset($json['aircraft']['reg'])) {
                        $json['general']['registration'] = $json['aircraft']['reg'];
                    }

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
