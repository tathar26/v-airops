<?php

namespace App\Services;

use App\Jobs\SendNewVirtualAirlinePendingApprovalEmailJob;
use App\Models\Airport;
use App\Models\PilotProfile;
use App\Models\Rank;
use App\Models\Tenant;
use App\Models\TenantHub;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class VirtualAirlineCreationService
{
    protected CallsignGeneratorService $callsignService;

    public function __construct(CallsignGeneratorService $callsignService)
    {
        $this->callsignService = $callsignService;
    }

    /**
     * Create a new Virtual Airline with duplicate ICAO prevention, all required settings, base hub, ranks, and owner enrollment.
     */
    public function createVirtualAirline(array $data, ?User $creator = null, ?UploadedFile $logoFile = null): Tenant
    {
        $icao = strtoupper(trim($data['icao'] ?? ''));
        if (empty($icao) || strlen($icao) < 2 || strlen($icao) > 4) {
            throw ValidationException::withMessages([
                'icao' => 'The Airline ICAO code must be between 2 and 4 characters.',
            ]);
        }

        // 1. Strict Duplicate Check on ICAO code
        $existingIcao = Tenant::whereRaw('UPPER(icao) = ?', [$icao])->first();
        if ($existingIcao) {
            throw ValidationException::withMessages([
                'icao' => "The ICAO code '{$icao}' is already registered by '{$existingIcao->name}'. Duplicate airlines are not permitted.",
            ]);
        }

        // 2. Base Hub Airport lookup / fetch
        $baseIcao = strtoupper(trim($data['base_hub_icao'] ?? ''));
        if (strlen($baseIcao) !== 4) {
            throw ValidationException::withMessages([
                'base_hub_icao' => 'Base Hub ICAO must be a 4-letter airport code (e.g. EGLL, KJFK, EHAM).',
            ]);
        }

        $airport = Airport::fetchAndCreate($baseIcao);
        if (!$airport) {
            throw ValidationException::withMessages([
                'base_hub_icao' => "Airport with ICAO '{$baseIcao}' could not be found or fetched.",
            ]);
        }

        return DB::transaction(function () use ($data, $creator, $logoFile, $airport, $icao, $baseIcao) {
            $logoPath = null;
            if ($logoFile) {
                $logoPath = $logoFile->store('logos', 'public');
            }

            // Determine initial approval state:
            // If created by a Master Admin, auto-approve; if created by a pilot, requires Master Admin approval.
            $isMasterAdmin = $creator && $creator->hasRole('Master Admin');
            $isApproved = $isMasterAdmin ? true : false;
            $status = $isMasterAdmin ? 'active' : 'pending';
            $approvedBy = $isMasterAdmin ? $creator->id : null;
            $approvedAt = $isMasterAdmin ? now() : null;

            $tenant = Tenant::create([
                'name' => trim($data['name']),
                'domain' => !empty($data['domain']) ? trim($data['domain']) : null,
                'icao' => $icao,
                'accent_color' => $data['accent_color'] ?? '#f97316',
                'bg_color' => $data['bg_color'] ?? '#1e1e1e',
                'default_simbrief_ofp_format' => $data['default_simbrief_ofp_format'] ?? 'lido',
                'logo_path' => $logoPath,
                'is_approved' => $isApproved,
                'status' => $status,
                'created_by' => $creator ? $creator->id : null,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
            ]);

            // Create Base Hub
            TenantHub::firstOrCreate([
                'tenant_id' => $tenant->id,
                'airport_id' => $airport->id,
                'is_base' => true,
            ]);

            // Create Default Ranks
            \App\Services\RankProgressionService::seedDefaultRanks($tenant);

            // If a user is creating this VA
            if ($creator) {
                $vaOwnerRole = Role::firstOrCreate(['name' => 'VA Owner']);
                $creator->assignRole($vaOwnerRole);

                if ($creator->roles->isEmpty() || !$creator->hasRole('Pilot')) {
                    $pilotRole = Role::firstOrCreate(['name' => 'Pilot']);
                    $creator->assignRole($pilotRole);
                }

                // Enroll creator as Captain with generated callsign
                $this->callsignService->assignUserToAirline($creator, $tenant, 'Captain');

                // Switch active airline to this new VA
                session(['active_airline_id' => $tenant->id]);
                $creator->tenant_id = $tenant->id;
                $creator->save();

                // Create profile with location set to base airport
                PilotProfile::firstOrCreate(
                    ['user_id' => $creator->id, 'tenant_id' => $tenant->id],
                    ['current_airport_id' => $airport->id, 'flight_time' => 0, 'points' => 0]
                );

                // If created by a pilot (not Master Admin), send email notification to System Admins for approval
                if (!$isMasterAdmin) {
                    SendNewVirtualAirlinePendingApprovalEmailJob::dispatch($tenant, $creator, $baseIcao);
                }
            }

            return $tenant;
        });
    }
}
