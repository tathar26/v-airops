<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimBriefService
{
    /**
     * Generate a flight plan via SimBrief API.
     *
     * @param string $username The pilot's SimBrief username
     * @param array $flightData The flight details (e.g., origin, destination, type)
     * @return array|null
     */
    public function generateFlightPlan(string $username, array $flightData)
    {
        // Example integration: In a real scenario, SimBrief uses an XML API or JSON
        // The URL typically is: https://www.simbrief.com/api/xml.fetcher.php?username={$username}&json=1
        
        $url = "https://www.simbrief.com/api/xml.fetcher.php";
        
        try {
            $response = Http::get($url, [
                'username' => $username,
                'json' => 1,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SimBrief API Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SimBrief API Exception: ' . $e->getMessage());
            return null;
        }
    }
}
