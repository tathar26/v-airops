# Graph Report - v-ops  (2026-09-14)

## Corpus Check
- 377 files · ~213,611 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1957 nodes · 3580 edges · 294 communities (87 shown, 73 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 40 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6813f6c5`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- V-Air Ops — Pilot & Dispatcher User Guide
- TenantHub
- PilotProfile
- Illuminate\Database\Eloquent\Relations\BelongsTo
- User
- TestCase
- Airport
- Illuminate\Database\Eloquent\Model
- Livewire\Component
- V-Air Ops — Airline Management & Staff Operations Manual
- AircraftType
- Docker & Docker Compose Installation Guide
- UserAirlineRole
- FortifyServiceProvider.php
- Tenant
- HasAirlineNotams
- Rank
- WithTenantRolesAndPermissions
- HasPilotRanks
- V-Air Ops
- RecalculatePilotStatistics
- Airframe
- Production Deployment Guide
- HasCallsignMappings
- Features & System Capabilities
- CreateNewUser.php
- Dispatch
- RankProgressionService
- EmailQueueManager
- Route
- 2. 🔍 Common Issues & Quick Resolutions
- Booking
- PirepsList
- Changelog
- Notam
- modals.blade.php
- Closure
- package.json
- HasTenantContext
- Carbon\Carbon
- ScheduleImportService
- tenant-settings.blade.php
- .submit
- ActivitySystemTest
- AirlineRole
- PasswordResetTest.php
- ActivitySlot
- Configuration & Environment Variables Reference
- JetstreamServiceProvider
- Controller
- flight-map.js
- Activity
- roles-tab.blade.php
- GlobalNetworkImport
- UserStatistic
- CallsignGeneratorService
- Pirep
- dispatch.blade.php
- Illuminate\Database\Schema\Blueprint
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Migrations\Migration
- ActivityRegistration
- users-tab.blade.php
- AcarsActiveFlight
- fleet-manager.blade.php
- MasterAdminDashboard
- general-tab.blade.php
- Carbon
- DemoDataSeeder.php
- [v1.1.28] - 2026-09-08
- Illuminate\Http\JsonResponse
- [v1.1.24] - 2026-09-07
- route-manager.blade.php
- TwoFactorAuthenticationSettingsTest.php
- airport-manager.blade.php
- Illuminate\Support\Facades\Hash
- Preferences.php
- composer.json
- require-dev
- scripts
- rank-manager.blade.php
- scoring-tab.blade.php
- live-flight-map.js
- Illuminate\View\Component
- email-queue-manager.blade.php
- require
- aircraft-type-manager.blade.php
- config
- api-token-manager.blade.php
- preferences.blade.php
- notam-manager.blade.php
- UpdatePasswordTest.php
- global-network-import.blade.php
- pirep-detail-view.blade.php
- master-admin-dashboard.blade.php
- Illuminate\Http\Request
- psr-4
- logging.php
- sanctum.php
- notams-list.blade.php
- logout-other-browser-sessions-form.blade.php
- delete-user-form.blade.php
- 2026_08_11_124524_create_passkeys_table.php
- ExampleTest
- bootstrap/app.php
- .processPirep
- autoload-dev
- extra
- confirms-password.blade.php
- V-Air Ops — Wiki Documentation
- FlightCentreController
- activity-manager.blade.php
- ActivityTeam
- [v1.1.12] - 2026-09-07
- [v1.1.13] - 2026-09-07
- [v1.1.22] - 2026-09-07
- Notam.php
- [v1.1.7] - 2026-09-04
- activity-detail.blade.php
- PirepDetail
- ProfileInformationTest.php
- [v1.1.25] - 2026-09-07
- [v1.1.19] - 2026-09-07
- activities-list.blade.php
- deleteProfilePhoto
- start.sh
- legal.privacy
- legal.terms
- refreshStatistics
- admin/pireps-list.blade.php
- account-settings.blade.php
- rules/graphify.md
- workflows/graphify.md
- [v1.1.16] - 2026-09-07
- [v1.1.26] - 2026-09-07
- [v1.1.27] - 2026-09-07
- [v1.1.35] - 2026-09-08
- [v1.1.36] - 2026-09-08
- [v1.1.10] - 2026-09-04
- [v1.1.18] - 2026-09-07
- [v1.1.17] - 2026-09-07
- [v1.1.20] - 2026-09-07
- [v1.1.30] - 2026-09-08
- [v1.1.9] - 2026-09-04
- [v1.1.34] - 2026-09-08
- [v1.0.9] - 2026-08-19
- [v1.1.14] - 2026-09-07
- [v1.1.32] - 2026-09-08
- [v1.1.31] - 2026-09-08
- [v1.1.11] - 2026-09-06
- [v1.1.8] - 2026-09-04
- [v1.1.21] - 2026-09-07
- [v1.1.39] - 2026-09-14
- [v1.1.15] - 2026-09-07
- removeHub({{ $hub->id }})
- policy.md
- terms.md
- [v1.1.23] - 2026-09-07
- [v1.1.38] - 2026-09-14
- curated-rosters.blade.php

## God Nodes (most connected - your core abstractions)
1. `User` - 161 edges
2. `Tenant` - 112 edges
3. `Pirep` - 75 edges
4. `Activity` - 60 edges
5. `Rank` - 48 edges
6. `PilotProfile` - 45 edges
7. `TestCase` - 40 edges
8. `Route` - 38 edges
9. `Dispatch` - 36 edges
10. `Changelog` - 36 edges

## Surprising Connections (you probably didn't know these)
- `ActivitySystemTest` --references--> `Tenant`  [EXTRACTED]
  tests/Feature/ActivitySystemTest.php → app/Models/Tenant.php
- `RankSystemTest` --references--> `Tenant`  [EXTRACTED]
  tests/Feature/RankSystemTest.php → app/Models/Tenant.php
- `ActivitySystemTest` --references--> `User`  [EXTRACTED]
  tests/Feature/ActivitySystemTest.php → app/Models/User.php
- `RankSystemTest` --references--> `User`  [EXTRACTED]
  tests/Feature/RankSystemTest.php → app/Models/User.php
- `ActivitySystemTest` --references--> `ActivityService`  [EXTRACTED]
  tests/Feature/ActivitySystemTest.php → app/Services/ActivityService.php

## Import Cycles
- None detected.

## Communities (294 total, 73 thin omitted)

### Community 0 - "V-Air Ops — Pilot & Dispatcher User Guide"
Cohesion: 0.10
Nodes (21): 1. Account Setup & Multi-Airline Enrollment, 2. Exploring Routes & Fleet, 3. Booking Flights & Dispatching with SimBrief, 4. In-Flight Tracking & Live 3D Radar, 5. PIREPs, Touchdown Analysis & Scoring, 6. Pilot Profile, Rank Progression & Preferences, Automated PIREP Ingestion, Creating a Booking (+13 more)

### Community 1 - "TenantHub"
Cohesion: 0.10
Nodes (6): WithTenantGeneralSettings, TenantHub, Illuminate\Http\UploadedFile, Spatie\Permission\DefaultTeamResolver, Spatie\Permission\Models\Permission, Spatie\Permission\Models\Role

### Community 3 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.11
Nodes (4): ActivityLegProgress, NotamUserRead, PirepComment, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 4 - "User"
Cohesion: 0.07
Nodes (10): User, RoutePolicy, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Fortify\TwoFactorAuthenticatable, Laravel\Jetstream\HasProfilePhoto, Laravel\Sanctum\HasApiTokens, Spatie\Permission\Traits\HasRoles (+2 more)

### Community 5 - "TestCase"
Cohesion: 0.08
Nodes (18): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Laravel\Jetstream\Features, Laravel\Jetstream\Http\Livewire\ApiTokenManager, Laravel\Jetstream\Http\Livewire\DeleteUserForm, Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm, Laravel\Jetstream\Http\Middleware\AuthenticateSession, Livewire\Livewire (+10 more)

### Community 7 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.15
Nodes (9): ScoringCriteria, SystemGlobalAircraft, SystemGlobalAirframe, SystemGlobalAirline, SystemGlobalAirport, SystemGlobalFlight, BelongsToTenant, Illuminate\Database\Eloquent\Factories\HasFactory (+1 more)

### Community 8 - "Livewire\Component"
Cohesion: 0.11
Nodes (8): AccountSettings, Awards, Map, PirepsList, TenantSettings, Livewire\Component, Livewire\WithFileUploads, Livewire\WithPagination

### Community 9 - "V-Air Ops — Airline Management & Staff Operations Manual"
Cohesion: 0.13
Nodes (15): 1. Airline Configuration & Branding, 2. Hub & Airport Management, 3. Fleet & Airframe Administration, 4. Routes, Schedules & Global Imports, 5. Rank Structures & Promotion Criteria, 6. PIREP Review & Safety Enforcement, 7. NOTAM Operations System, 8. Multi-Tenant Role-Based Access Control (RBAC) (+7 more)

### Community 10 - "AircraftType"
Cohesion: 0.18
Nodes (3): AircraftTypeManager, AircraftType, AcarsApiTest

### Community 11 - "Docker & Docker Compose Installation Guide"
Cohesion: 0.14
Nodes (14): 1. 🐳 Container Ecosystem Overview, 2. 📦 Container Specifications & Port Mapping, 3. 💾 Persistent Volumes & Mount Points, 4. 🛠️ Step-by-Step Production Deployment, 5. 🔄 Staging & Local Development Stacks, 6. 📋 Routine Container Management Commands, Docker & Docker Compose Installation Guide, Local Development Environment (`docker-compose.local.yml`) (+6 more)

### Community 12 - "UserAirlineRole"
Cohesion: 0.20
Nodes (3): WithTenantUserManagement, UserAirlineRole, Illuminate\Database\Eloquent\Relations\Pivot

### Community 13 - "FortifyServiceProvider.php"
Cohesion: 0.13
Nodes (10): AppServiceProvider, FortifyServiceProvider, Illuminate\Auth\Events\Verified, Illuminate\Cache\RateLimiting\Limit, Illuminate\Support\Facades\Event, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\Facades\URL, Illuminate\Support\ServiceProvider (+2 more)

### Community 14 - "Tenant"
Cohesion: 0.10
Nodes (3): WithTenantScoringSettings, Tenant, ScoringService

### Community 15 - "HasAirlineNotams"
Cohesion: 0.32
Nodes (3): HasAirlineNotams, Collection, Illuminate\Database\Eloquent\Collection

### Community 17 - "WithTenantRolesAndPermissions"
Cohesion: 0.07
Nodes (8): WithTenantRolesAndPermissions, AirlinePermission, App\Models\Team, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Str, Pdo\Mysql, static

### Community 19 - "V-Air Ops"
Cohesion: 0.15
Nodes (13): 1. Prerequisites, 2. Setup & Configuration, 3. Launch Services, 📚 Complete Wiki Documentation Suite, 🌟 Core System Capabilities, ✈️ For Flight Simulation Pilots, 🏢 For Virtual Airline Owners & Operations Staff, 🏗️ High-Level Architecture (+5 more)

### Community 20 - "RecalculatePilotStatistics"
Cohesion: 0.05
Nodes (33): AssignMissingCallsignsCommand, ImportGlobalSchedules, ImportOperatorFleet, PurgeUnflownBookingsCommand, VerifyUserCommand, AssignMissingCallsignsJob, ImportAirlineSchedulesJob, ProcessPirepsAndRecalculateStatsJob (+25 more)

### Community 21 - "Airframe"
Cohesion: 0.12
Nodes (3): FleetManager, TenantDashboard, Airframe

### Community 22 - "Production Deployment Guide"
Cohesion: 0.15
Nodes (13): 1. 🖥️ System Requirements & Prerequisites, 2. 🚀 Bare-Metal LEMP Installation, 3. 🌐 Nginx Web Server & Reverse Proxy Configuration, 4. ⚙️ Supervisor Background Queue Workers & Scheduler, 5. 🚀 Production Caching & Performance Checklist, Laravel Scheduler Cron Job, Production Deployment Guide, Recommended Production Server Specifications (+5 more)

### Community 24 - "Features & System Capabilities"
Cohesion: 0.17
Nodes (12): 1. 🏢 Multi-Tenant Virtual Airline Cloud, 2. 📡 Real-Time ACARS Telemetry & Flight Tracking, 3. 🛬 Precision PIREP Scoring & Evaluation Engine, 4. 🗺️ Global Network Import & Schedule Hub, 5. 🎛️ Interactive Search Selection & Management Hubs, 6. ✈️ SimBrief Integration & Dispatch Console, Airport Management, Features & System Capabilities (+4 more)

### Community 25 - "CreateNewUser.php"
Cohesion: 0.11
Nodes (13): CreateNewUser, PasswordValidationRules, ResetUserPassword, UpdateUserPassword, UpdateUserProfileInformation, Illuminate\Contracts\Auth\MustVerifyEmail, Illuminate\Support\Facades\Validator, Illuminate\Validation\Rule (+5 more)

### Community 28 - "EmailQueueManager"
Cohesion: 0.14
Nodes (4): EmailQueueManager, Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan, Illuminate\Support\Facades\Schedule

### Community 30 - "2. 🔍 Common Issues & Quick Resolutions"
Cohesion: 0.18
Nodes (11): 1. 📂 Critical Log Locations, 1. HTTP 500 Internal Server Error, 2. 🔍 Common Issues & Quick Resolutions, 2. Livewire Component or Blade Syntax Errors, 3. Background Jobs / Global Imports Stuck or Not Running, 3. 🧹 Routine Maintenance Playbook, 4. Database Schema Out of Sync or Missing Columns, 5. Telemetry Ingestion / ACARS Connection Issues (+3 more)

### Community 33 - "Changelog"
Cohesion: 0.20
Nodes (9): 🐛 Account Deletion 500 Error Fix, 🐛 Bug Fix: Pilot Profile Resolution & Tenant Settings User Roster, Changelog, 🐛 Hotfix: PHP Array Syntax & Blade Directive Encapsulation in Homepage JSON-LD, 🔄 Past PIREP Recalculation Engine, [v1.1.29] - 2026-09-08, [v1.1.33] - 2026-09-08, [v1.1.37] - 2026-09-08 (+1 more)

### Community 34 - "Notam"
Cohesion: 0.10
Nodes (3): NotamManager, NotamsList, Notam

### Community 35 - "modals.blade.php"
Cohesion: 0.20
Nodes (9): clearPermissions, $set(, saveCustomPermission, saveRole, saveUser, saveUserRoles, selectAllPermissions, selectAllReadPermissions (+1 more)

### Community 36 - "Closure"
Cohesion: 0.26
Nodes (6): EnsureActiveAirlineSelected, EnsureAirlinePermission, EnsureNotamsAcknowledged, EnsureSystemAdmin, Closure, Symfony\Component\HttpFoundation\Response

### Community 37 - "package.json"
Cohesion: 0.07
Nodes (25): devDependencies, autoprefixer, concurrently, laravel-vite-plugin, postcss, tailwindcss, @tailwindcss/forms, @tailwindcss/typography (+17 more)

### Community 39 - "Carbon\Carbon"
Cohesion: 0.17
Nodes (4): ActivityContribution, ActivityLeg, Carbon\Carbon, Illuminate\Support\Facades\DB

### Community 40 - "ScheduleImportService"
Cohesion: 0.15
Nodes (4): ScheduleImportService, Generator, Illuminate\Http\Client\PendingRequest, RuntimeException

### Community 41 - "tenant-settings.blade.php"
Cohesion: 0.22
Nodes (8): livewire.tenant-settings.partials.general-tab, livewire.tenant-settings.partials.hubs-tab, livewire.tenant-settings.partials.modals, livewire.tenant-settings.partials.roles-tab, livewire.tenant-settings.partials.scoring-tab, livewire.tenant-settings.partials.users-tab, rank-manager, $set(

### Community 45 - "AirlineRole"
Cohesion: 0.16
Nodes (3): AirlineRole, HasAirlineRolesAndPermissions, Illuminate\Support\Collection

### Community 46 - "PasswordResetTest.php"
Cohesion: 0.25
Nodes (3): Illuminate\Auth\Notifications\ResetPassword, Illuminate\Support\Facades\Notification, PasswordResetTest

### Community 48 - "Configuration & Environment Variables Reference"
Cohesion: 0.25
Nodes (8): 1. ⚙️ Application & Core Settings, 2. 🗄️ Database Configuration, 3. 🚀 Cache, Session & Queue Drivers, 4. ✉️ Email & Notification Services, 5. 🌍 Aviation APIs & External Integrations, 6. 📦 Storage & File System (Optional S3 / Cloud Storage), 7. 🔒 Production Security Example (`.env`), Configuration & Environment Variables Reference

### Community 49 - "JetstreamServiceProvider"
Cohesion: 0.24
Nodes (4): DeleteUser, JetstreamServiceProvider, Laravel\Jetstream\Contracts\DeletesUsers, Laravel\Jetstream\Jetstream

### Community 50 - "Controller"
Cohesion: 0.19
Nodes (4): FleetController, RouteController, Controller, Illuminate\Support\Facades\Route

### Community 51 - "flight-map.js"
Cohesion: 0.20
Nodes (14): drawMarker(), drawRoute(), fetchData(), getAirport(), hideTooltip(), init(), initMap(), jumpseat() (+6 more)

### Community 52 - "Activity"
Cohesion: 0.10
Nodes (4): Activity, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Eloquent\Relations\HasManyThrough

### Community 53 - "roles-tab.blade.php"
Cohesion: 0.29
Nodes (6): deleteCustomPermission({{ $cp->id }}), deleteRole({{ $role->id }}), editCustomPermission({{ $cp->id }}), editRole({{ $role->id }}), openCreatePermissionModal, openCreateRoleModal

### Community 56 - "CallsignGeneratorService"
Cohesion: 0.39
Nodes (3): AirlineOnboardingController, CallsignGeneratorService, VirtualAirlineCreationService

### Community 58 - "dispatch.blade.php"
Cohesion: 0.12
Nodes (15): cancelAndRebook, cancelBooking, cancelLoadingState, clearHoldBags, clearPassengers, createBooking, dispatchSimbriefPopup, downloadFmsFile (+7 more)

### Community 62 - "ActivityRegistration"
Cohesion: 0.13
Nodes (3): ActivitiesList, CuratedRosters, ActivityRegistration

### Community 63 - "users-tab.blade.php"
Cohesion: 0.33
Nodes (5): deleteUser({{ $user->id }}), editUser({{ $user->id }}), openManageUserRolesModal({{ $user->id }}), openUserModal, removeQuickRole({{ $user->id }}, {{ $r->id }})

### Community 64 - "AcarsActiveFlight"
Cohesion: 0.08
Nodes (7): AcarsController, FlightEventRequest, TelemetryPingRequest, AcarsActiveFlight, AcarsEvent, AcarsPosition, Illuminate\Database\Eloquent\Relations\HasOne

### Community 65 - "fleet-manager.blade.php"
Cohesion: 0.13
Nodes (14): deleteAirframe({{ $airframe->id }}), editAirframe({{ $airframe->id }}), fetchApiFleet, importAllApiAirframes, importGlobalAirframes, importSelectedApiAirframes, importSelectedRealWorldAirframes, downloadTemplate (+6 more)

### Community 67 - "general-tab.blade.php"
Cohesion: 0.40
Nodes (4): addCallsignMapping, addSecondaryIcao, removeCallsignMapping({{ $index }}), removeSecondaryIcao({{ $index }})

### Community 68 - "Carbon"
Cohesion: 0.12
Nodes (3): ActivityManager, Carbon, DemoDashboardSeeder

### Community 69 - "DemoDataSeeder.php"
Cohesion: 0.27
Nodes (4): DatabaseSeeder, DemoDataSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 71 - "Illuminate\Http\JsonResponse"
Cohesion: 0.12
Nodes (7): AuthController, FlightController, DispatchFlightRequest, LoginRequest, PirepSubmitRequest, Illuminate\Foundation\Http\FormRequest, Illuminate\Http\JsonResponse

### Community 73 - "route-manager.blade.php"
Cohesion: 0.17
Nodes (11): applyMassUpdate, deleteRoute({{ $route->id }}), editRoute({{ $route->id }}), massDelete, openMassUpdateModal, downloadTemplate, importCsv, openAddModal (+3 more)

### Community 74 - "TwoFactorAuthenticationSettingsTest.php"
Cohesion: 0.15
Nodes (4): Laravel\Fortify\Features, Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm, RegistrationTest, TwoFactorAuthenticationSettingsTest

### Community 75 - "airport-manager.blade.php"
Cohesion: 0.20
Nodes (9): addMetadataField, deleteAirport({{ $airport->id }}), editAirport({{ $airport->id }}), fetchAirportData, openModal, removeMetadataField({{ $index }}), resetFilters, $set( (+1 more)

### Community 76 - "Illuminate\Support\Facades\Hash"
Cohesion: 0.20
Nodes (5): SessionAirlineController, Illuminate\Contracts\Validation\Rule, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Hash, Illuminate\Validation\Rules\Password

### Community 77 - "Preferences.php"
Cohesion: 0.28
Nodes (4): VersionService, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Storage

### Community 78 - "composer.json"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 79 - "require-dev"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision (+1 more)

### Community 80 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 81 - "rank-manager.blade.php"
Cohesion: 0.22
Nodes (8): deleteRank({{ $rank->id }}), editRank({{ $rank->id }}), moveRankDown({{ $rank->id }}), moveRankUp({{ $rank->id }}), openModal(false), openModal(true), $set(, saveRank

### Community 82 - "scoring-tab.blade.php"
Cohesion: 0.50
Nodes (3): recalculateAirlinePireps, resetScoringSettings, saveScoringSettings

### Community 83 - "live-flight-map.js"
Cohesion: 0.44
Nodes (6): fetchLiveFlights(), init(), initMap(), renderMap(), renderRoutes(), selectFlight()

### Community 84 - "Illuminate\View\Component"
Cohesion: 0.43
Nodes (4): AppLayout, GuestLayout, Illuminate\View\Component, Illuminate\View\View

### Community 85 - "email-queue-manager.blade.php"
Cohesion: 0.25
Nodes (7): clearAllFailed, deleteFailedJob({{ $job[, deleteJob({{ $job[, purgePendingQueue, setTab(, retryAllFailed, retryFailedJob({{ $job[

### Community 86 - "require"
Cohesion: 0.25
Nodes (8): require, laravel/framework, laravel/jetstream, laravel/sanctum, laravel/tinker, livewire/livewire, php, spatie/laravel-permission

### Community 87 - "aircraft-type-manager.blade.php"
Cohesion: 0.25
Nodes (7): deleteAircraftType({{ $type->id }}), editAircraftType({{ $type->id }}), importGlobalTypes, openAddModal, openGlobalImportModal, $set(, saveAircraftType

### Community 88 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 89 - "api-token-manager.blade.php"
Cohesion: 0.29
Nodes (6): confirmApiTokenDeletion({{ $token->id }}), deleteApiToken, manageApiTokenPermissions({{ $token->id }}), $set(, $toggle(, updateApiToken

### Community 90 - "preferences.blade.php"
Cohesion: 0.29
Nodes (6): confirmDelete, confirmReset, deleteAccount, resetAccount, $toggle(, savePreferences

### Community 91 - "notam-manager.blade.php"
Cohesion: 0.29
Nodes (6): deleteNotam({{ $notam->id }}), editNotam({{ $notam->id }}), openCreateModal, $set(, saveNotam, viewReads({{ $notam->id }})

### Community 93 - "global-network-import.blade.php"
Cohesion: 0.33
Nodes (5): checkApiStatus, closeImportModal, confirmImport, openImportModal(, search

### Community 94 - "pirep-detail-view.blade.php"
Cohesion: 0.40
Nodes (4): accept, invalidate, reject, requestReply

### Community 95 - "master-admin-dashboard.blade.php"
Cohesion: 0.40
Nodes (4): approveVirtualAirline({{ $pending->id }}), closeCreateVaModal, openCreateVaModal, rejectVirtualAirline({{ $pending->id }})

### Community 96 - "Illuminate\Http\Request"
Cohesion: 0.16
Nodes (4): AcarsController, FlightCentreApiController, CustomAuthController, Illuminate\Http\Request

### Community 97 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 98 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 99 - "sanctum.php"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 100 - "notams-list.blade.php"
Cohesion: 0.50
Nodes (3): acknowledgeNotam({{ $selectedNotam->id }}), closeModal, openNotam({{ $notam->id }})

### Community 102 - "logout-other-browser-sessions-form.blade.php"
Cohesion: 0.50
Nodes (3): confirmLogout, logoutOtherBrowserSessions, $toggle(

### Community 103 - "delete-user-form.blade.php"
Cohesion: 0.50
Nodes (3): confirmUserDeletion, deleteUser, $toggle(

### Community 106 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 108 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 109 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 111 - "V-Air Ops — Wiki Documentation"
Cohesion: 0.50
Nodes (4): ⚙️ Core Architecture Concepts, 🧭 Repository Ecosystem Map, 📑 Table of Contents, V-Air Ops — Wiki Documentation

### Community 114 - "activity-manager.blade.php"
Cohesion: 0.15
Nodes (12): addLeg, addWave, copyActivity({{ $activity->id }}), deleteActivity({{ $activity->id }}), editActivity({{ $activity->id }}), removeLeg({{ $index }}), removeWave({{ $index }}), reprocessActivity({{ $activity->id }}) (+4 more)

### Community 120 - "[v1.1.12] - 2026-09-07"
Cohesion: 0.67
Nodes (3): 🛠️ Bug Fixes & Reliability, ✈️ SimBrief Profile Integration & Dispatch Overhaul, [v1.1.12] - 2026-09-07

### Community 135 - "[v1.1.13] - 2026-09-07"
Cohesion: 0.67
Nodes (3): 📊 Pilot Statistics & Logbook Overhaul, [v1.1.13] - 2026-09-07, 📡 vPilot ACARS Telemetry Enhancements

### Community 145 - "activity-detail.blade.php"
Cohesion: 0.33
Nodes (5): bookSlot({{ $slot->id }}), register, releaseSlot({{ $slot->id }}), $set(, unregister

### Community 159 - "activities-list.blade.php"
Cohesion: 0.50
Nodes (3): register({{ $activity->id }}), setTab(, unregister({{ $activity->id }})

## Knowledge Gaps
- **360 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+355 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 864 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **73 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `TenantHub`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `TestCase`, `Airport`, `Illuminate\Database\Eloquent\Model`, `Livewire\Component`, `AircraftType`, `UserAirlineRole`, `FortifyServiceProvider.php`, `Tenant`, `Notam.php`, `HasAirlineNotams`, `WithTenantRolesAndPermissions`, `HasPilotRanks`, `RecalculatePilotStatistics`, `CreateNewUser.php`, `Dispatch`, `ProfileInformationTest.php`, `RankProgressionService`, `Notam`, `HasTenantContext`, `ActivitySystemTest`, `AirlineRole`, `PasswordResetTest.php`, `JetstreamServiceProvider`, `Activity`, `ActivityRegistration`, `AcarsActiveFlight`, `MasterAdminDashboard`, `Carbon`, `DemoDataSeeder.php`, `Illuminate\Http\JsonResponse`, `TwoFactorAuthenticationSettingsTest.php`, `Illuminate\Support\Facades\Hash`, `Preferences.php`, `UpdatePasswordTest.php`, `Illuminate\Http\Request`?**
  _High betweenness centrality (0.109) - this node is a cross-community bridge._
- **Why does `Tenant` connect `Tenant` to `TenantHub`, `PilotProfile`, `User`, `TestCase`, `Illuminate\Database\Eloquent\Model`, `Livewire\Component`, `AircraftType`, `HasAirlineNotams`, `Rank`, `WithTenantRolesAndPermissions`, `HasPilotRanks`, `RecalculatePilotStatistics`, `HasCallsignMappings`, `RankProgressionService`, `Booking`, `HasTenantContext`, `ScheduleImportService`, `ActivitySystemTest`, `AirlineRole`, `Activity`, `GlobalNetworkImport`, `CallsignGeneratorService`, `ActivityRegistration`, `AcarsActiveFlight`, `MasterAdminDashboard`, `Carbon`, `DemoDataSeeder.php`, `Illuminate\Support\Facades\Hash`, `Preferences.php`?**
  _High betweenness centrality (0.070) - this node is a cross-community bridge._
- **Why does `Pirep` connect `Pirep` to `TenantHub`, `PilotProfile`, `Illuminate\Database\Eloquent\Model`, `Livewire\Component`, `Tenant`, `Rank`, `RecalculatePilotStatistics`, `Airframe`, `PirepDetail`, `RankProgressionService`, `Booking`, `PirepsList`, `Carbon\Carbon`, `.submit`, `ActivitySystemTest`, `AirlineRole`, `UserStatistic`, `ActivityRegistration`, `AcarsActiveFlight`, `MasterAdminDashboard`, `Carbon`, `Preferences.php`, `Illuminate\Http\Request`, `.processPirep`?**
  _High betweenness centrality (0.028) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _360 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `V-Air Ops — Pilot & Dispatcher User Guide` be split into smaller, more focused modules?**
  _Cohesion score 0.09523809523809523 - nodes in this community are weakly interconnected._
- **Should `TenantHub` be split into smaller, more focused modules?**
  _Cohesion score 0.09956709956709957 - nodes in this community are weakly interconnected._
- **Should `PilotProfile` be split into smaller, more focused modules?**
  _Cohesion score 0.14705882352941177 - nodes in this community are weakly interconnected._