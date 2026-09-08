<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'icao',
        'secondary_icaos',
        'callsign_mappings',
        'accent_color',
        'bg_color',
        'panel_bg_color',
        'panel_text_color',
        'card_bg_color',
        'card_text_color',
        'card_muted_text_color',
        'button_bg_color',
        'button_text_color',
        'button_secondary_bg_color',
        'button_secondary_text_color',
        'input_bg_color',
        'input_text_color',
        'input_border_color',
        'logo_path',
        'default_simbrief_ofp_format',
        'scoring_settings',
        'is_approved',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'secondary_icaos' => 'array',
        'callsign_mappings' => 'array',
        'scoring_settings' => 'array',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get callsign prefix mappings configured for this tenant.
     * Normalized as an array of ['callsign_prefix' => string, 'flight_number_prefix' => string].
     *
     * @return array<int, array{callsign_prefix: string, flight_number_prefix: string}>
     */
    public function getCallsignMappings(): array
    {
        $mappings = $this->callsign_mappings ?? [];
        if (!is_array($mappings)) {
            return [];
        }

        $formatted = [];
        foreach ($mappings as $key => $value) {
            if (is_array($value) && isset($value['callsign_prefix'], $value['flight_number_prefix'])) {
                $cs = strtoupper(trim((string)$value['callsign_prefix']));
                $fn = strtoupper(trim((string)$value['flight_number_prefix']));
                if (!empty($cs) && !empty($fn)) {
                    $formatted[] = ['callsign_prefix' => $cs, 'flight_number_prefix' => $fn];
                }
            } elseif (is_string($key) && is_string($value)) {
                $cs = strtoupper(trim($key));
                $fn = strtoupper(trim($value));
                if (!empty($cs) && !empty($fn)) {
                    $formatted[] = ['callsign_prefix' => $cs, 'flight_number_prefix' => $fn];
                }
            }
        }

        return $formatted;
    }

    /**
     * Get the commercial flight number prefix for a specific airline ICAO code.
     *
     * Example:
     *   - Tenant mapped EJU -> EC: returns "EC"
     *   - Tenant mapped EZY -> U2: returns "U2"
     *   - Built-in BAW -> returns "BA"
     *   - Unknown ICAO "VOPS" -> returns "VOPS"
     *
     * @param string|null $targetIcao 3-letter ICAO code (e.g. EJU, EZY, BAW)
     * @return string Commercial flight number prefix (e.g. EC, U2, BA)
     */
    public function getFlightNumberPrefixForIcao(?string $targetIcao = null): string
    {
        $icaoUpper = strtoupper(trim($targetIcao ?? ''));
        if (empty($icaoUpper)) {
            $icaoUpper = strtoupper(trim($this->icao ?: 'VOPS'));
        }

        // 1. Check custom tenant callsign mappings
        $mappings = $this->getCallsignMappings();
        foreach ($mappings as $map) {
            if ($map['callsign_prefix'] === $icaoUpper && !empty($map['flight_number_prefix'])) {
                return $map['flight_number_prefix'];
            }
        }

        // 2. Default well-known ICAO -> IATA dictionary fallback
        $defaultIcaoToIata = [
            'EZY' => 'U2', 'EZS' => 'DS', 'EJU' => 'EC',
            'RYR' => 'FR', 'RUK' => 'RK', 'MAY' => 'M4',
            'BAW' => 'BA', 'KLM' => 'KL', 'DLH' => 'LH',
            'AFR' => 'AF', 'WZZ' => 'W6', 'WUK' => 'W9',
            'THY' => 'TK', 'SAS' => 'SK', 'FIN' => 'AY',
            'IBE' => 'IB', 'TAP' => 'TP', 'SWR' => 'LX',
            'AUA' => 'OS', 'BEL' => 'SN', 'UAE' => 'EK',
            'QTR' => 'QR', 'ETD' => 'EY', 'QFA' => 'QF',
            'ANZ' => 'NZ', 'SIA' => 'SQ', 'CPA' => 'CX',
            'ANA' => 'NH', 'JAL' => 'JL', 'AAL' => 'AA',
            'DAL' => 'DL', 'UAL' => 'UA', 'SWA' => 'WN',
            'ACA' => 'AC', 'VLG' => 'VY', 'TRA' => 'HV',
            'AZA' => 'AZ', 'EIN' => 'EI', 'NVR' => 'N9',
            'GWI' => '4U', 'VOE' => 'V7', 'EXS' => 'LS',
            'TOM' => 'BY', 'EWG' => 'EW', 'TUI' => 'X3',
        ];

        if (isset($defaultIcaoToIata[$icaoUpper])) {
            return $defaultIcaoToIata[$icaoUpper];
        }

        return $icaoUpper;
    }

    /**
     * Extract the flight number / callsign suffix from an incoming schedule callsign.
     * Strips leading letters or an explicit strip prefix.
     *
     * Example: "KLM82A" -> "82A", "EZY8412" -> "8412", "TOM456" -> "456"
     *
     * @param string $callsign
     * @param string|null $stripPrefix
     * @return string
     */
    public function extractCallsignSuffix(string $callsign, ?string $stripPrefix = null): string
    {
        $clean = strtoupper(trim($callsign));
        if (empty($clean)) {
            return '1001';
        }

        if (!empty($stripPrefix)) {
            $pfx = strtoupper(trim($stripPrefix));
            if (str_starts_with($clean, $pfx)) {
                $suffix = substr($clean, strlen($pfx));
                if (!empty($suffix)) {
                    return $suffix;
                }
            }
        }

        // Auto-strip leading 2-4 letter airline code (e.g., KLM82A -> 82A, BAW1420 -> 1420, EZY8412 -> 8412)
        $stripped = preg_replace('/^[A-Z]{2,4}/', '', $clean);
        return !empty($stripped) ? $stripped : $clean;
    }

    /**
     * Resolve commercial flight number for an imported route based on the target airline ICAO.
     *
     * Example:
     *   - Target ICAO = EJU (mapped to EC) with callsign KLM82A -> returns "EC82A"
     *   - Target ICAO = EZY (mapped to U2) with callsign BAW1420 -> returns "U21420"
     *
     * @param string $callsign Incoming schedule callsign (e.g. KLM82A)
     * @param string|null $targetIcao Target airline ICAO (e.g. EJU, EZY)
     * @param string|null $stripPrefix Optional explicit prefix to strip
     * @return string Commercial flight number (e.g. EC82A, U21420)
     */
    public function resolveFlightNumberForTarget(string $callsign, ?string $targetIcao = null, ?string $stripPrefix = null): string
    {
        $targetIcao = !empty($targetIcao) ? strtoupper(trim($targetIcao)) : ($this->icao ?: 'VOPS');
        $fnPrefix = $this->getFlightNumberPrefixForIcao($targetIcao);
        $suffix = $this->extractCallsignSuffix($callsign, $stripPrefix);

        return $fnPrefix . $suffix;
    }

    /**
     * Resolve a commercial flight number from an ATC callsign based on tenant mapping rules
     * or built-in well-known airline mappings.
     *
     * @param string $callsign ATC Callsign (e.g. EZY8412, KLM1234)
     * @param string|null $airlineIcao Airline 3-letter ICAO (e.g. EZY)
     * @return string Generated commercial flight number (e.g. U28412)
     */
    public function resolveFlightNumber(string $callsign, ?string $airlineIcao = null): string
    {
        $cleanCallsign = strtoupper(trim($callsign));
        if (empty($cleanCallsign)) {
            return 'FL0001';
        }

        // 1. Check custom tenant callsign mappings
        $mappings = $this->getCallsignMappings();
        foreach ($mappings as $map) {
            $csPrefix = $map['callsign_prefix'];
            $fnPrefix = $map['flight_number_prefix'];

            if (!empty($csPrefix) && !empty($fnPrefix)) {
                if (str_starts_with($cleanCallsign, $csPrefix)) {
                    $suffix = substr($cleanCallsign, strlen($csPrefix));
                    return $fnPrefix . $suffix;
                }
            }
        }

        // 2. Default well-known ICAO -> IATA dictionary fallback
        $defaultIcaoToIata = [
            'EZY' => 'U2', 'EZS' => 'DS', 'EJU' => 'EC',
            'RYR' => 'FR', 'RUK' => 'RK', 'MAY' => 'M4',
            'BAW' => 'BA', 'KLM' => 'KL', 'DLH' => 'LH',
            'AFR' => 'AF', 'WZZ' => 'W6', 'WUK' => 'W9',
            'THY' => 'TK', 'SAS' => 'SK', 'FIN' => 'AY',
            'IBE' => 'IB', 'TAP' => 'TP', 'SWR' => 'LX',
            'AUA' => 'OS', 'BEL' => 'SN', 'UAE' => 'EK',
            'QTR' => 'QR', 'ETD' => 'EY', 'QFA' => 'QF',
            'ANZ' => 'NZ', 'SIA' => 'SQ', 'CPA' => 'CX',
            'ANA' => 'NH', 'JAL' => 'JL', 'AAL' => 'AA',
            'DAL' => 'DL', 'UAL' => 'UA', 'SWA' => 'WN',
            'ACA' => 'AC', 'VLG' => 'VY', 'TRA' => 'HV',
            'AZA' => 'AZ', 'EIN' => 'EI', 'NVR' => 'N9',
            'GWI' => '4U', 'VOE' => 'V7', 'EXS' => 'LS',
            'TOM' => 'BY', 'EWG' => 'EW', 'TUI' => 'X3',
        ];

        $prefix3 = substr($cleanCallsign, 0, 3);
        if (isset($defaultIcaoToIata[$prefix3])) {
            return $defaultIcaoToIata[$prefix3] . substr($cleanCallsign, 3);
        }

        if (!empty($airlineIcao) && isset($defaultIcaoToIata[strtoupper($airlineIcao)])) {
            $iata = $defaultIcaoToIata[strtoupper($airlineIcao)];
            $stripped = preg_replace('/^[A-Z0-9]{2,3}/', '', $cleanCallsign);
            $suffix = !empty($stripped) ? $stripped : $cleanCallsign;
            return $iata . $suffix;
        }

        // 3. Fallback: preserve callsign
        return $cleanCallsign;
    }



    /**
     * Get all airline ICAOs (primary default ICAO + all configured secondary ICAOs).
     *
     * @return array<int, string>
     */
    public function getAllIcaos(): array
    {
        $list = [];
        if (!empty($this->icao)) {
            $list[] = strtoupper(trim($this->icao));
        }

        if (!empty($this->secondary_icaos) && is_array($this->secondary_icaos)) {
            foreach ($this->secondary_icaos as $code) {
                $code = strtoupper(trim((string)$code));
                if (!empty($code) && !in_array($code, $list)) {
                    $list[] = $code;
                }
            }
        }

        return empty($list) ? ['VOPS'] : array_values($list);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function userAirlines()
    {
        return $this->hasMany(UserAirline::class, 'tenant_id');
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'user_airlines', 'tenant_id', 'user_id')
                    ->withPivot(['callsign', 'join_date', 'rank', 'is_active'])
                    ->withTimestamps();
    }

    public function hubs()
    {
        return $this->hasMany(TenantHub::class);
    }

    /**
     * Custom roles defined for this airline.
     */
    public function roles()
    {
        return $this->hasMany(AirlineRole::class, 'tenant_id');
    }

    /**
     * NOTAMs issued for this airline.
     */
    public function notams()
    {
        return $this->hasMany(Notam::class, 'tenant_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Default scoring criteria settings for virtual airlines.
     */
    public static function defaultScoringSettings(): array
    {
        return [
            // Base Starting Points
            'base_points' => 150,

            // Landing Touchdown Rate (FPM) Criteria & Points
            'fpm_butter_threshold' => 120,
            'fpm_butter_points' => 50,
            'fpm_good_threshold' => 200,
            'fpm_good_points' => 30,
            'fpm_fair_threshold' => 350,
            'fpm_fair_points' => 15,
            'fpm_firm_threshold' => 500,
            'fpm_firm_penalty' => 10,
            'fpm_hard_threshold' => 650,
            'fpm_hard_penalty' => 30,
            'fpm_reject_threshold' => 650,
            'fpm_reject_penalty' => 60,
            'fpm_danger_threshold' => 800,
            'fpm_danger_penalty' => 100,

            // Engine Operations
            'engine_warmup_seconds' => 180,
            'engine_warmup_penalty' => 30,
            'engine_cooldown_seconds' => 180,
            'engine_cooldown_penalty' => 30,
            'engine_start_interval_seconds' => 60,
            'engine_start_interval_bonus' => 10,
            'engines_shutdown_clean_bonus' => 10,

            // Flaps Operations
            'flaps_parking_bonus' => 10,
            'flaps_retracted_violation_penalty' => 10,
            'takeoff_flaps_penalty' => 10,

            // Fuel Operations
            'min_landing_fuel_kg' => 1000,
            'low_fuel_penalty' => 50,
            'max_landing_fuel_kg' => 5000,
            'excess_fuel_penalty' => 25,

            // Network & Social Bonuses
            'online_network_bonus' => 50,
            'shared_cockpit_bonus' => 50,
            'prep_time_bonus' => 25,

            // Flight Duration Bonuses
            'flight_time_bonus_under_1h' => 10,
            'flight_time_bonus_1_to_2h' => 25,
            'flight_time_bonus_2_to_3h' => 50,
            'flight_time_bonus_3_to_4h' => 75,
            'flight_time_bonus_over_4h' => 100,

            // Failure Rules
            'max_sim_rate' => 1.0,
            'max_bounces' => 1,
            'invalidate_on_negative_score' => true,
        ];
    }

    /**
     * Get effective scoring criteria for this airline (custom merged over defaults).
     */
    public function getScoringSettings(): array
    {
        $defaults = static::defaultScoringSettings();
        $custom = $this->scoring_settings ?? [];

        if (!is_array($custom)) {
            return $defaults;
        }

        return array_merge($defaults, array_filter($custom, fn($val) => !is_null($val)));
    }

    /**
     * Custom permissions created by this virtual airline.
     */
    public function customPermissions()
    {
        return $this->hasMany(AirlinePermission::class, 'tenant_id');
    }
}
