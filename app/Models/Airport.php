<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = ['icao', 'name', 'lat', 'lon', 'elevation', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tenantHubs()
    {
        return $this->hasMany(TenantHub::class);
    }

    /**
     * Fetch airport data from external API and create if it doesn't exist
     */
    public static function fetchAndCreate($icao)
    {
        $icao = strtoupper($icao);
        $airport = self::where('icao', $icao)->first();
        
        if ($airport) {
            return $airport;
        }

        try {
            // Fetch from mwgg/Airports repository JSON
            $response = \Illuminate\Support\Facades\Http::get('https://raw.githubusercontent.com/mwgg/Airports/master/airports.json');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data[$icao])) {
                    $airportData = $data[$icao];
                    
                    return self::create([
                        'icao' => $icao,
                        'name' => $airportData['name'] ?? $icao,
                        'lat' => $airportData['lat'] ?? 0,
                        'lon' => $airportData['lon'] ?? 0,
                        'elevation' => $airportData['elevation'] ?? null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log or ignore
        }

        return null;
    }
}
