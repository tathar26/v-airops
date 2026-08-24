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
        'is_approved',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'secondary_icaos' => 'array',
        'callsign_mappings' => 'array',
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
     * Resolve a commercial flight number from an ATC callsign based on tenant mapping rules
     * or built-in well-known airline mappings.
     *
     * Example:
     *   - Tenant mapped EZY -> U2: "EZY8412" -> "U28412"
     *   - Tenant mapped BAW -> BA: "BAW1420" -> "BA1420"
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

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
