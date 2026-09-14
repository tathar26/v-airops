# Graph Report - v-ops  (2026-09-14)

## Corpus Check
- 377 files · ~213,548 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1959 nodes · 3576 edges · 289 communities (84 shown, 73 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 47 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `15115cc9`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- V-Air Ops — Pilot & Dispatcher User Guide
- TenantHub
- PilotProfile
- Illuminate\Database\Eloquent\Relations\BelongsTo
- User
- TestCase
- Illuminate\Support\Str
- Illuminate\Database\Eloquent\Model
- Livewire\Component
- V-Air Ops — Airline Management & Staff Operations Manual
- AircraftType
- Docker & Docker Compose Installation Guide
- UserAirlineRole
- NotamsList
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
- FortifyServiceProvider.php
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
- AirlineRole
- ScheduleImportService
- tenant-settings.blade.php
- PirepController.php
- Illuminate\Database\Eloquent\Builder
- HasAirlineRolesAndPermissions
- EmailVerificationTest.php
- UpdateUserProfileInformation.php
- Configuration & Environment Variables Reference
- Illuminate\Support\ServiceProvider
- Controller
- flight-map.js
- Activity
- roles-tab.blade.php
- GlobalNetworkImport
- Preferences.php
- VirtualAirlineCreationService.php
- ActivityService
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
- DemoDashboardSeeder.php
- [v1.1.28] - 2026-09-08
- Illuminate\Http\JsonResponse
- [v1.1.24] - 2026-09-07
- route-manager.blade.php
- PasswordConfirmationTest
- airport-manager.blade.php
- AcarsApiTest.php
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
- Illuminate\Support\Facades\DB
- logout-other-browser-sessions-form.blade.php
- delete-user-form.blade.php
- 2026_08_11_124524_create_passkeys_table.php
- ExampleTest
- bootstrap/app.php
- autoload-dev
- extra
- confirms-password.blade.php
- V-Air Ops — Wiki Documentation
- activity-manager.blade.php
- ActivityTeam
- [v1.1.12] - 2026-09-07
- [v1.1.13] - 2026-09-07
- [v1.1.22] - 2026-09-07
- Notam.php
- [v1.1.7] - 2026-09-04
- activity-detail.blade.php
- Pirep
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
1. `User` - 155 edges
2. `Tenant` - 112 edges
3. `Activity` - 60 edges
4. `Pirep` - 49 edges
5. `Rank` - 48 edges
6. `PilotProfile` - 44 edges
7. `TestCase` - 38 edges
8. `Dispatch` - 36 edges
9. `Route` - 36 edges
10. `Changelog` - 36 edges

## Surprising Connections (you probably didn't know these)
- `ActivitySystemTest` --references--> `Tenant`  [EXTRACTED]
  tests/Feature/ActivitySystemTest.php → app/Models/Tenant.php
- `RankSystemTest` --references--> `Tenant`  [EXTRACTED]
  tests/Feature/RankSystemTest.php → app/Models/Tenant.php
- `ActivitySystemTest` --references--> `ActivityService`  [EXTRACTED]
  tests/Feature/ActivitySystemTest.php → app/Services/ActivityService.php
- `RankSystemTest` --references--> `User`  [EXTRACTED]
  tests/Feature/RankSystemTest.php → app/Models/User.php
- `TenantSettings` --mixes_in--> `WithTenantRolesAndPermissions`  [EXTRACTED]
  app/Livewire/TenantSettings.php → app/Livewire/Concerns/WithTenantRolesAndPermissions.php

## Import Cycles
- None detected.

## Communities (289 total, 73 thin omitted)

### Community 0 - "V-Air Ops — Pilot & Dispatcher User Guide"
Cohesion: 0.10
Nodes (21): 1. Account Setup & Multi-Airline Enrollment, 2. Exploring Routes & Fleet, 3. Booking Flights & Dispatching with SimBrief, 4. In-Flight Tracking & Live 3D Radar, 5. PIREPs, Touchdown Analysis & Scoring, 6. Pilot Profile, Rank Progression & Preferences, Automated PIREP Ingestion, Creating a Booking (+13 more)

### Community 1 - "TenantHub"
Cohesion: 0.14
Nodes (3): WithTenantGeneralSettings, TenantSettings, TenantHub

### Community 3 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.08
Nodes (6): ActivityContribution, ActivityLeg, ActivityLegProgress, PirepComment, Pirep, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 4 - "User"
Cohesion: 0.07
Nodes (12): App\Models\User, User, RoutePolicy, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Fortify\TwoFactorAuthenticatable, Laravel\Jetstream\HasProfilePhoto, Laravel\Sanctum\HasApiTokens (+4 more)

### Community 5 - "TestCase"
Cohesion: 0.08
Nodes (20): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Laravel\Jetstream\Features, Laravel\Jetstream\Http\Livewire\ApiTokenManager, Laravel\Jetstream\Http\Livewire\DeleteUserForm, Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm, Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm, Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm (+12 more)

### Community 6 - "Illuminate\Support\Str"
Cohesion: 0.18
Nodes (6): App\Models\Team, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Str, Pdo\Mysql, static

### Community 7 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.06
Nodes (21): AcarsEvent, AcarsPirep, AcarsPosition, App\Models\AircraftType, App\Models\Airframe, App\Models\Airport, NotamUserRead, App\Models\PilotProfile (+13 more)

### Community 8 - "Livewire\Component"
Cohesion: 0.15
Nodes (5): AccountSettings, Awards, Dashboard, Map, Livewire\Component

### Community 9 - "V-Air Ops — Airline Management & Staff Operations Manual"
Cohesion: 0.13
Nodes (15): 1. Airline Configuration & Branding, 2. Hub & Airport Management, 3. Fleet & Airframe Administration, 4. Routes, Schedules & Global Imports, 5. Rank Structures & Promotion Criteria, 6. PIREP Review & Safety Enforcement, 7. NOTAM Operations System, 8. Multi-Tenant Role-Based Access Control (RBAC) (+7 more)

### Community 11 - "Docker & Docker Compose Installation Guide"
Cohesion: 0.14
Nodes (14): 1. 🐳 Container Ecosystem Overview, 2. 📦 Container Specifications & Port Mapping, 3. 💾 Persistent Volumes & Mount Points, 4. 🛠️ Step-by-Step Production Deployment, 5. 🔄 Staging & Local Development Stacks, 6. 📋 Routine Container Management Commands, Docker & Docker Compose Installation Guide, Local Development Environment (`docker-compose.local.yml`) (+6 more)

### Community 12 - "UserAirlineRole"
Cohesion: 0.17
Nodes (3): WithTenantUserManagement, UserAirlineRole, Illuminate\Database\Eloquent\Relations\Pivot

### Community 15 - "HasAirlineNotams"
Cohesion: 0.32
Nodes (3): HasAirlineNotams, Collection, Illuminate\Database\Eloquent\Collection

### Community 19 - "V-Air Ops"
Cohesion: 0.15
Nodes (13): 1. Prerequisites, 2. Setup & Configuration, 3. Launch Services, 📚 Complete Wiki Documentation Suite, 🌟 Core System Capabilities, ✈️ For Flight Simulation Pilots, 🏢 For Virtual Airline Owners & Operations Staff, 🏗️ High-Level Architecture (+5 more)

### Community 20 - "RecalculatePilotStatistics"
Cohesion: 0.06
Nodes (32): AssignMissingCallsignsCommand, ImportGlobalSchedules, ImportOperatorFleet, PurgeUnflownBookingsCommand, VerifyUserCommand, AssignMissingCallsignsJob, ImportAirlineSchedulesJob, ProcessPirepsAndRecalculateStatsJob (+24 more)

### Community 22 - "Production Deployment Guide"
Cohesion: 0.15
Nodes (13): 1. 🖥️ System Requirements & Prerequisites, 2. 🚀 Bare-Metal LEMP Installation, 3. 🌐 Nginx Web Server & Reverse Proxy Configuration, 4. ⚙️ Supervisor Background Queue Workers & Scheduler, 5. 🚀 Production Caching & Performance Checklist, Laravel Scheduler Cron Job, Production Deployment Guide, Recommended Production Server Specifications (+5 more)

### Community 23 - "HasCallsignMappings"
Cohesion: 0.21
Nodes (4): App\Models\Concerns\HasCallsignMappings, HasCallsignMappings, App\Models\Concerns\HasTenantScoring, HasTenantScoring

### Community 24 - "Features & System Capabilities"
Cohesion: 0.17
Nodes (12): 1. 🏢 Multi-Tenant Virtual Airline Cloud, 2. 📡 Real-Time ACARS Telemetry & Flight Tracking, 3. 🛬 Precision PIREP Scoring & Evaluation Engine, 4. 🗺️ Global Network Import & Schedule Hub, 5. 🎛️ Interactive Search Selection & Management Hubs, 6. ✈️ SimBrief Integration & Dispatch Console, Airport Management, Features & System Capabilities (+4 more)

### Community 25 - "FortifyServiceProvider.php"
Cohesion: 0.10
Nodes (15): CreateNewUser, PasswordValidationRules, ResetUserPassword, UpdateUserPassword, Illuminate\Cache\RateLimiting\Limit, Illuminate\Contracts\Validation\Rule, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\RateLimiter (+7 more)

### Community 26 - "Dispatch"
Cohesion: 0.05
Nodes (5): AirportManager, Dispatch, Airport, SimBriefService, Illuminate\Support\Facades\Http

### Community 28 - "EmailQueueManager"
Cohesion: 0.14
Nodes (4): EmailQueueManager, Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan, Illuminate\Support\Facades\Schedule

### Community 30 - "2. 🔍 Common Issues & Quick Resolutions"
Cohesion: 0.18
Nodes (11): 1. 📂 Critical Log Locations, 1. HTTP 500 Internal Server Error, 2. 🔍 Common Issues & Quick Resolutions, 2. Livewire Component or Blade Syntax Errors, 3. Background Jobs / Global Imports Stuck or Not Running, 3. 🧹 Routine Maintenance Playbook, 4. Database Schema Out of Sync or Missing Columns, 5. Telemetry Ingestion / ACARS Connection Issues (+3 more)

### Community 33 - "Changelog"
Cohesion: 0.20
Nodes (9): 🐛 Account Deletion 500 Error Fix, 🐛 Bug Fix: Pilot Profile Resolution & Tenant Settings User Roster, Changelog, 🐛 Hotfix: PHP Array Syntax & Blade Directive Encapsulation in Homepage JSON-LD, 🔄 Past PIREP Recalculation Engine, [v1.1.29] - 2026-09-08, [v1.1.33] - 2026-09-08, [v1.1.37] - 2026-09-08 (+1 more)

### Community 35 - "modals.blade.php"
Cohesion: 0.20
Nodes (9): clearPermissions, $set(, saveCustomPermission, saveRole, saveUser, saveUserRoles, selectAllPermissions, selectAllReadPermissions (+1 more)

### Community 36 - "Closure"
Cohesion: 0.26
Nodes (6): EnsureActiveAirlineSelected, EnsureAirlinePermission, EnsureNotamsAcknowledged, EnsureSystemAdmin, Closure, Symfony\Component\HttpFoundation\Response

### Community 37 - "package.json"
Cohesion: 0.08
Nodes (25): devDependencies, autoprefixer, concurrently, laravel-vite-plugin, postcss, tailwindcss, @tailwindcss/forms, @tailwindcss/typography (+17 more)

### Community 40 - "ScheduleImportService"
Cohesion: 0.15
Nodes (4): ScheduleImportService, Generator, Illuminate\Http\Client\PendingRequest, RuntimeException

### Community 41 - "tenant-settings.blade.php"
Cohesion: 0.22
Nodes (8): livewire.tenant-settings.partials.general-tab, livewire.tenant-settings.partials.hubs-tab, livewire.tenant-settings.partials.modals, livewire.tenant-settings.partials.roles-tab, livewire.tenant-settings.partials.scoring-tab, livewire.tenant-settings.partials.users-tab, rank-manager, $set(

### Community 42 - "PirepController.php"
Cohesion: 0.23
Nodes (3): PirepController, PirepSubmitRequest, ScoringService

### Community 46 - "EmailVerificationTest.php"
Cohesion: 0.11
Nodes (7): Illuminate\Auth\Events\Verified, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Support\Facades\Event, Illuminate\Support\Facades\Notification, Laravel\Fortify\Features, PasswordResetTest, RegistrationTest

### Community 47 - "UpdateUserProfileInformation.php"
Cohesion: 0.38
Nodes (4): UpdateUserProfileInformation, Illuminate\Contracts\Auth\MustVerifyEmail, Illuminate\Validation\Rule, Laravel\Fortify\Contracts\UpdatesUserProfileInformation

### Community 48 - "Configuration & Environment Variables Reference"
Cohesion: 0.25
Nodes (8): 1. ⚙️ Application & Core Settings, 2. 🗄️ Database Configuration, 3. 🚀 Cache, Session & Queue Drivers, 4. ✉️ Email & Notification Services, 5. 🌍 Aviation APIs & External Integrations, 6. 📦 Storage & File System (Optional S3 / Cloud Storage), 7. 🔒 Production Security Example (`.env`), Configuration & Environment Variables Reference

### Community 49 - "Illuminate\Support\ServiceProvider"
Cohesion: 0.13
Nodes (8): DeleteUser, AppServiceProvider, FortifyServiceProvider, JetstreamServiceProvider, Illuminate\Support\Facades\URL, Illuminate\Support\ServiceProvider, Laravel\Jetstream\Contracts\DeletesUsers, Laravel\Jetstream\Jetstream

### Community 50 - "Controller"
Cohesion: 0.12
Nodes (5): FleetController, RouteController, Controller, FlightCentreController, Illuminate\Support\Facades\Route

### Community 51 - "flight-map.js"
Cohesion: 0.20
Nodes (14): drawMarker(), drawRoute(), fetchData(), getAirport(), hideTooltip(), init(), initMap(), jumpseat() (+6 more)

### Community 52 - "Activity"
Cohesion: 0.10
Nodes (7): Activity, User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Eloquent\Relations\HasManyThrough, ActivitySystemTest, User, Tests\TestCase

### Community 53 - "roles-tab.blade.php"
Cohesion: 0.29
Nodes (6): deleteCustomPermission({{ $cp->id }}), deleteRole({{ $role->id }}), editCustomPermission({{ $cp->id }}), editRole({{ $role->id }}), openCreatePermissionModal, openCreateRoleModal

### Community 55 - "Preferences.php"
Cohesion: 0.12
Nodes (4): Preferences, UserStatistic, VersionService, Illuminate\Support\Facades\Cache

### Community 56 - "VirtualAirlineCreationService.php"
Cohesion: 0.24
Nodes (5): AirlineOnboardingController, CallsignGeneratorService, VirtualAirlineCreationService, Illuminate\Http\UploadedFile, Illuminate\Validation\ValidationException

### Community 57 - "ActivityService"
Cohesion: 0.25
Nodes (3): App\Models\Pirep, PirepObserver, ActivityService

### Community 58 - "dispatch.blade.php"
Cohesion: 0.12
Nodes (15): cancelAndRebook, cancelBooking, cancelLoadingState, clearHoldBags, clearPassengers, createBooking, dispatchSimbriefPopup, downloadFmsFile (+7 more)

### Community 62 - "ActivityRegistration"
Cohesion: 0.11
Nodes (7): ActivitiesList, ActivityDetail, CuratedRosters, ActivityRegistration, ActivitySlot, Carbon\Carbon, Livewire\WithPagination

### Community 63 - "users-tab.blade.php"
Cohesion: 0.33
Nodes (5): deleteUser({{ $user->id }}), editUser({{ $user->id }}), openManageUserRolesModal({{ $user->id }}), openUserModal, removeQuickRole({{ $user->id }}, {{ $r->id }})

### Community 64 - "AcarsActiveFlight"
Cohesion: 0.14
Nodes (5): AcarsController, FlightEventRequest, TelemetryPingRequest, AcarsActiveFlight, Illuminate\Foundation\Http\FormRequest

### Community 65 - "fleet-manager.blade.php"
Cohesion: 0.13
Nodes (14): deleteAirframe({{ $airframe->id }}), editAirframe({{ $airframe->id }}), fetchApiFleet, importAllApiAirframes, importGlobalAirframes, importSelectedApiAirframes, importSelectedRealWorldAirframes, downloadTemplate (+6 more)

### Community 67 - "general-tab.blade.php"
Cohesion: 0.40
Nodes (4): addCallsignMapping, addSecondaryIcao, removeCallsignMapping({{ $index }}), removeSecondaryIcao({{ $index }})

### Community 68 - "Carbon"
Cohesion: 0.11
Nodes (3): ActivityManager, ActivityWave, Carbon

### Community 69 - "DemoDashboardSeeder.php"
Cohesion: 0.17
Nodes (8): DatabaseSeeder, DemoDashboardSeeder, DemoDataSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Spatie\Permission\DefaultTeamResolver, Spatie\Permission\Models\Permission, Spatie\Permission\Models\Role

### Community 71 - "Illuminate\Http\JsonResponse"
Cohesion: 0.15
Nodes (5): AuthController, FlightController, DispatchFlightRequest, LoginRequest, Illuminate\Http\JsonResponse

### Community 73 - "route-manager.blade.php"
Cohesion: 0.17
Nodes (11): applyMassUpdate, deleteRoute({{ $route->id }}), editRoute({{ $route->id }}), massDelete, openMassUpdateModal, downloadTemplate, importCsv, openAddModal (+3 more)

### Community 75 - "airport-manager.blade.php"
Cohesion: 0.20
Nodes (9): addMetadataField, deleteAirport({{ $airport->id }}), editAirport({{ $airport->id }}), fetchAirportData, openModal, removeMetadataField({{ $index }}), resetFilters, $set( (+1 more)

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
Cohesion: 0.12
Nodes (6): AcarsController, FlightCentreApiController, CustomAuthController, SessionAirlineController, Illuminate\Http\Request, Illuminate\Support\Facades\Auth

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
Nodes (12): addLeg, addWave, copyActivity({{ $activity->id }}), deleteActivity({{ $activity->id }}), editActivity({{ $activity->id }}), openCreateModal, removeLeg({{ $index }}), removeWave({{ $index }}) (+4 more)

### Community 120 - "[v1.1.12] - 2026-09-07"
Cohesion: 0.67
Nodes (3): 🛠️ Bug Fixes & Reliability, ✈️ SimBrief Profile Integration & Dispatch Overhaul, [v1.1.12] - 2026-09-07

### Community 135 - "[v1.1.13] - 2026-09-07"
Cohesion: 0.67
Nodes (3): 📊 Pilot Statistics & Logbook Overhaul, [v1.1.13] - 2026-09-07, 📡 vPilot ACARS Telemetry Enhancements

### Community 145 - "activity-detail.blade.php"
Cohesion: 0.33
Nodes (5): bookSlot({{ $slot->id }}), register, releaseSlot({{ $slot->id }}), $set(, unregister

### Community 151 - "Pirep"
Cohesion: 0.13
Nodes (5): PirepDetail, PirepsList, Pirep, App\Services\PirepScoringService, PirepScoringService

### Community 159 - "activities-list.blade.php"
Cohesion: 0.50
Nodes (3): register({{ $activity->id }}), setTab(, unregister({{ $activity->id }})

## Knowledge Gaps
- **360 isolated node(s):** `openCreateModal`, `setTypeTab(`, `reprocessActivity({{ $activity->id }})`, `copyActivity({{ $activity->id }})`, `editActivity({{ $activity->id }})` (+355 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 863 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **73 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `TenantHub`, `TestCase`, `Illuminate\Support\Str`, `Illuminate\Database\Eloquent\Model`, `UserAirlineRole`, `Tenant`, `Notam.php`, `HasAirlineNotams`, `HasPilotRanks`, `RecalculatePilotStatistics`, `FortifyServiceProvider.php`, `Dispatch`, `RankProgressionService`, `Notam`, `HasTenantContext`, `HasAirlineRolesAndPermissions`, `EmailVerificationTest.php`, `UpdateUserProfileInformation.php`, `Illuminate\Support\ServiceProvider`, `Preferences.php`, `VirtualAirlineCreationService.php`, `AcarsActiveFlight`, `MasterAdminDashboard`, `DemoDashboardSeeder.php`, `Illuminate\Http\JsonResponse`, `PasswordConfirmationTest`, `AcarsApiTest.php`, `UpdatePasswordTest.php`, `Illuminate\Http\Request`?**
  _High betweenness centrality (0.089) - this node is a cross-community bridge._
- **Why does `Tenant` connect `Tenant` to `TenantHub`, `User`, `TestCase`, `Illuminate\Database\Eloquent\Model`, `HasAirlineNotams`, `Rank`, `HasPilotRanks`, `RecalculatePilotStatistics`, `HasCallsignMappings`, `Pirep`, `RankProgressionService`, `Booking`, `HasTenantContext`, `ScheduleImportService`, `PirepController.php`, `Illuminate\Database\Eloquent\Builder`, `HasAirlineRolesAndPermissions`, `Activity`, `GlobalNetworkImport`, `VirtualAirlineCreationService.php`, `MasterAdminDashboard`, `DemoDashboardSeeder.php`, `AcarsApiTest.php`, `Illuminate\Http\Request`?**
  _High betweenness centrality (0.081) - this node is a cross-community bridge._
- **Why does `Activity` connect `Activity` to `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Carbon`, `Illuminate\Database\Eloquent\Model`, `Illuminate\Database\Eloquent\Builder`, `ActivityService`, `ActivityRegistration`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **What connects `openCreateModal`, `setTypeTab(`, `reprocessActivity({{ $activity->id }})` to the rest of the system?**
  _360 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `V-Air Ops — Pilot & Dispatcher User Guide` be split into smaller, more focused modules?**
  _Cohesion score 0.09523809523809523 - nodes in this community are weakly interconnected._
- **Should `TenantHub` be split into smaller, more focused modules?**
  _Cohesion score 0.13725490196078433 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Database\Eloquent\Relations\BelongsTo` be split into smaller, more focused modules?**
  _Cohesion score 0.08199643493761141 - nodes in this community are weakly interconnected._