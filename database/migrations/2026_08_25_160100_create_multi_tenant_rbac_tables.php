<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Roles: Scoped per Airline (tenant_id)
        if (!Schema::hasTable('airline_roles')) {
            Schema::create('airline_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete()
                    ->comment('The Airline that owns this role');
                $table->string('name')->comment('e.g. Chief Pilot, Route Manager, Line Pilot');
                $table->string('slug');
                $table->string('description')->nullable();
                
                // Honorary Staff Rank Assignment
                $table->string('honorary_rank_string')
                    ->nullable()
                    ->comment('Honorary title (e.g. Chief Pilot, VP Operations, Route Director)');
                $table->boolean('is_staff')
                    ->default(false)
                    ->comment('Designates whether this is an administrative/staff role');
                $table->boolean('is_default')
                    ->default(false)
                    ->comment('Default role automatically assigned to pilots joining this airline');

                $table->timestamps();

                // Unique role slug per airline
                $table->unique(['tenant_id', 'slug'], 'airline_roles_tenant_slug_unique');
            });
        }

        // 2. Granular Permissions (System-wide catalog)
        if (!Schema::hasTable('airline_permissions')) {
            Schema::create('airline_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('Display name: e.g. Edit Routes');
                $table->string('slug')->unique()->comment('e.g. view_routes, edit_routes, manage_fleet');
                $table->string('group')->default('general')->comment('e.g. routes, fleet, pireps, staff');
                $table->string('description')->nullable();
                $table->timestamps();
            });

            // Seed default granular permissions
            $permissions = [
                // Routes
                ['name' => 'View Routes', 'slug' => 'view_routes', 'group' => 'Routes', 'description' => 'View airline route network and schedules'],
                ['name' => 'Create Routes', 'slug' => 'create_routes', 'group' => 'Routes', 'description' => 'Create new flight routes and import schedules'],
                ['name' => 'Edit Routes', 'slug' => 'edit_routes', 'group' => 'Routes', 'description' => 'Modify existing flight routes and assignments'],
                ['name' => 'Delete Routes', 'slug' => 'delete_routes', 'group' => 'Routes', 'description' => 'Delete routes from airline network'],
                
                // Fleet
                ['name' => 'View Fleet', 'slug' => 'view_fleet', 'group' => 'Fleet', 'description' => 'View airline aircraft types and airframe fleet'],
                ['name' => 'Manage Fleet', 'slug' => 'manage_fleet', 'group' => 'Fleet', 'description' => 'Add, edit, or remove airframes from airline fleet'],
                ['name' => 'Manage Aircraft Types', 'slug' => 'manage_aircraft_types', 'group' => 'Fleet', 'description' => 'Create and configure aircraft types'],

                // PIREPs
                ['name' => 'View PIREPs', 'slug' => 'view_pireps', 'group' => 'PIREPs', 'description' => 'View all submitted pilot reports'],
                ['name' => 'Review PIREPs', 'slug' => 'review_pireps', 'group' => 'PIREPs', 'description' => 'Accept, reject, or adjust pilot reports'],
                ['name' => 'Delete PIREPs', 'slug' => 'delete_pireps', 'group' => 'PIREPs', 'description' => 'Delete flight reports from the database'],

                // Operations & Settings
                ['name' => 'Manage Airports', 'slug' => 'manage_airports', 'group' => 'Airports', 'description' => 'Configure airline airport hubs and bases'],
                ['name' => 'Manage Ranks', 'slug' => 'manage_ranks', 'group' => 'Ranks & Roles', 'description' => 'Configure flight-hour rank criteria and points'],
                ['name' => 'Manage Roles & Staff', 'slug' => 'manage_roles', 'group' => 'Ranks & Roles', 'description' => 'Create custom roles, assign permissions and honorary ranks'],
                ['name' => 'Manage Pilots', 'slug' => 'manage_pilots', 'group' => 'Pilots', 'description' => 'Manage enrolled pilots and callsign assignments'],
                ['name' => 'Manage Airline Settings', 'slug' => 'manage_airline_settings', 'group' => 'Settings', 'description' => 'Update branding, theme colors, and SimBrief defaults'],
                ['name' => 'View Finance & Stats', 'slug' => 'view_finance', 'group' => 'Analytics', 'description' => 'View operational financial breakdown and pilot stats'],
            ];

            $now = now();
            foreach ($permissions as &$p) {
                $p['created_at'] = $now;
                $p['updated_at'] = $now;
            }
            DB::table('airline_permissions')->insert($permissions);
        }

        // 3. Role <-> Permission Pivot
        if (!Schema::hasTable('airline_role_permissions')) {
            Schema::create('airline_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')
                    ->constrained('airline_roles')
                    ->cascadeOnDelete();
                $table->foreignId('permission_id')
                    ->constrained('airline_permissions')
                    ->cascadeOnDelete();

                $table->unique(['role_id', 'permission_id'], 'airline_role_perm_unique');
            });
        }

        // 4. User <-> Airline <-> Role Pivot (Context-specific Role assignment)
        if (!Schema::hasTable('user_airline_roles')) {
            Schema::create('user_airline_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete()
                    ->comment('The Airline context for this role');
                $table->foreignId('role_id')
                    ->constrained('airline_roles')
                    ->cascadeOnDelete();
                $table->timestamps();

                // A user can hold a specific role only once per airline
                $table->unique(['user_id', 'tenant_id', 'role_id'], 'user_airline_role_unique');
                $table->index(['user_id', 'tenant_id'], 'user_tenant_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_airline_roles');
        Schema::dropIfExists('airline_role_permissions');
        Schema::dropIfExists('airline_permissions');
        Schema::dropIfExists('airline_roles');
    }
};
