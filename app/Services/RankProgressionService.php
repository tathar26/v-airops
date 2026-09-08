<?php

namespace App\Services;

use App\Models\PilotProfile;
use App\Models\Pirep;
use App\Models\Rank;
use App\Models\Tenant;

class RankProgressionService
{
    /**
     * Evaluate and update a pilot's regular rank based on all 4 milestone criteria:
     * - Total flight hours
     * - Total flight points
     * - Cumulative bonus points
     * - Total accepted / complete PIREPs
     *
     * A pilot must meet ALL milestone criteria to qualify for a rank,
     * and receives the highest rank they qualify for according to position order.
     */
    public function evaluatePilotRank(PilotProfile $profile): ?Rank
    {
        $tenantId = $profile->tenant_id;
        $hours = (int) floor($profile->flight_time / 60);
        $points = (int) $profile->points;
        $bonusPoints = (int) ($profile->bonus_points ?? 0);

        // Count accepted/complete/approved PIREPs for this pilot in this airline
        $pirepsCount = Pirep::where('user_id', $profile->user_id)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['accepted', 'complete', 'approved'])
            ->count();

        // Get all regular ranks ordered from highest milestone/position down to lowest
        $ranks = Rank::where('tenant_id', $tenantId)
            ->where('is_honorary', false)
            ->orderBy('position', 'desc')
            ->orderBy('min_hours', 'desc')
            ->orderBy('min_points', 'desc')
            ->get();

        if ($ranks->isEmpty()) {
            return null;
        }

        $qualifyingRank = null;

        foreach ($ranks as $rank) {
            $meetsHours = $hours >= $rank->min_hours;
            $meetsPoints = $points >= $rank->min_points;
            $meetsBonus = $bonusPoints >= $rank->min_bonus_points;
            $meetsPireps = $pirepsCount >= $rank->min_pireps;

            if ($meetsHours && $meetsPoints && $meetsBonus && $meetsPireps) {
                $qualifyingRank = $rank;
                break;
            }
        }

        // If pilot does not qualify for any higher rank, assign base rank
        if (!$qualifyingRank) {
            $qualifyingRank = $ranks->sortBy('position')->first();
        }

        if ($qualifyingRank && $profile->rank_id !== $qualifyingRank->id) {
            $profile->rank_id = $qualifyingRank->id;
            $profile->save();
        }

        return $qualifyingRank;
    }

    /**
     * Seed default ranks for a Virtual Airline if missing.
     * Guaranteed defaults:
     * - Cadet (Regular, cannot be deleted)
     * - Staff Team (Honorary, cannot be deleted)
     * Plus standard progression ranks (Second Officer, First Officer, Captain).
     */
    public static function seedDefaultRanks(Tenant $tenant): void
    {
        // 1. Cadet (Default Regular Starting Rank)
        Rank::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Cadet'],
            [
                'abbreviation'     => 'Cdt',
                'position'         => 1,
                'min_hours'        => 0,
                'min_points'       => 0,
                'min_bonus_points' => 0,
                'min_pireps'       => 0,
                'is_honorary'      => false,
                'is_default'       => true,
                'image_path'       => 'epaulettes/epaulette-01.png',
            ]
        );

        // 2. Second Officer
        Rank::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Second Officer'],
            [
                'abbreviation'     => '2/O',
                'position'         => 2,
                'min_hours'        => 10,
                'min_points'       => 500,
                'min_bonus_points' => 0,
                'min_pireps'       => 5,
                'is_honorary'      => false,
                'is_default'       => false,
                'image_path'       => 'epaulettes/epaulette-02.png',
            ]
        );

        // 3. First Officer
        Rank::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'First Officer'],
            [
                'abbreviation'     => 'FO',
                'position'         => 3,
                'min_hours'        => 50,
                'min_points'       => 2500,
                'min_bonus_points' => 0,
                'min_pireps'       => 20,
                'is_honorary'      => false,
                'is_default'       => false,
                'image_path'       => 'epaulettes/epaulette-03.png',
            ]
        );

        // 4. Captain
        Rank::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Captain'],
            [
                'abbreviation'     => 'CPT',
                'position'         => 4,
                'min_hours'        => 150,
                'min_points'       => 10000,
                'min_bonus_points' => 0,
                'min_pireps'       => 50,
                'is_honorary'      => false,
                'is_default'       => false,
                'image_path'       => 'epaulettes/epaulette-04.png',
            ]
        );

        // 5. Staff Team (Default Honorary Rank)
        Rank::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Staff Team'],
            [
                'abbreviation'     => 'ST',
                'position'         => 999,
                'min_hours'        => 0,
                'min_points'       => 0,
                'min_bonus_points' => 0,
                'min_pireps'       => 0,
                'is_honorary'      => true,
                'is_default'       => true,
                'image_path'       => 'epaulettes/epaulette-staff.png',
            ]
        );
    }
}
