<?php

namespace App\Models\Concerns;

trait HasCallsignMappings
{
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
}
