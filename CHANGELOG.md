# Changelog

All notable changes to the Virtual Airline Operations (**V-Ops**) platform are documented in this file.

## [v1.1.34] - 2026-09-08

### 🎖️ Milestone Rank System & Security Policy Documentation
- **Recognition, Not Restriction Architecture:** Ranks serve strictly to celebrate pilot flight milestones and community recognition, never restricting aircraft types, routes, or airport bookings.
- **Regular Ranks (4-Criteria Milestones):** Automated progression requiring flight hours, total flight points, cumulative bonus points, and accepted PIREPs simultaneously. Ordered strictly from lowest to highest.
- **Honorary Ranks:** Manual staff recognition for instructors, staff members, and real-world pilots with pilot display preference toggle in preferences.
- **Default Protected Ranks:** Automatic seeding and deletion protection for default Cadet (`Cdt`) and Staff Team (`ST`) ranks upon virtual airline creation.
- **Custom 85×36 Epaulettes:** 7 built-in gold stripe presets (1–4 stripes, TRI, TRE, Staff Crest) plus support for custom image uploads.
- **Interactive UI & ACARS API:** Integrated epaulettes into pilot dashboard, user roster, and ACARS API auth payload.
- **Security Policy Documentation:** Added legal security policy view in legal views.

## [v1.1.33] - 2026-09-08

### 🔄 Past PIREP Recalculation Engine
- **CLI Recalculation Command:** Added `php artisan pireps:rescore-all` with `--tenant` and `--force-status` flags.
- **Web UI One-Click Recalculation:** Added recalculation action in Tenant Settings PIREP Scoring tab.

## [v1.1.32] - 2026-09-08

### 🎯 PIREP Scoring Overhaul, Dynamic Criteria Management, & Custom Permissions
- **FPM Touchdown Rate Metric:** Replaced G-force as the scoring and failure metric with vertical touchdown velocity in feet per minute (FPM). Evaluates Butter ($\le 120$ FPM), Good ($\le 200$ FPM), Fair ($\le 350$ FPM), Firm ($\le 500$ FPM), Hard ($\le 650$ FPM), Rejection ($\ge 650$ FPM), and Invalidation ($\ge 800$ FPM). G-force is preserved strictly for telemetry logging.
- **Dynamic VA Scoring Management View:** Added a dedicated **PIREP Scoring** tab in Virtual Airline Settings allowing airline owners to customize starting base points, FPM thresholds, engine/flaps operational tolerances, fuel penalties, and flight length bonuses with full adherence to dynamic VA theme tokens (`--tenant-card-bg`, `--tenant-input-bg`, `--tenant-accent`, etc.).
- **Custom Permissions System:** Enabled virtual airline owners to create custom granular permissions and assign them to any airline role with Read or Read-Write permissions matrix integration.
- **PIREP Display Score Cleanup:** Removed `/ 100 PTS` in the PIREP detail view so that points are cleanly displayed as `{{ $scoreVal }} PTS`.

---

## [v1.1.31] - 2026-09-08

### 🚀 Production CI/CD Build Trigger Fix
- **Tag Matcher & Workflow Dispatch:** Broadened tag glob trigger in `build-prod.yml` to `v*` and enabled `workflow_dispatch` manual execution.
- **Production Build Trigger:** Triggered production container build pipeline for Docker Hub (`tathar26/v-ops`).

---

## [v1.1.30] - 2026-09-08

### 🚀 GitHub Actions Workflows & V-AirOps ACARS Rebrand Alignment
- **GitHub Actions Workflows:** Configured automated workflows (`.github/workflows/build-devel.yml` and `.github/workflows/build-prod.yml`) for continuous Docker builds and tag deployments on the GitHub mirror (`https://github.com/tathar26/v-airops.git`).
- **ACARS Rebrand Integration:** Aligned all navigation menus, public landing page components, legal documents (Terms, Privacy, Security), and service configuration URLs to the newly rebranded **V-AirOps ACARS** desktop client.

---

## [v1.1.29] - 2026-09-08

### 🐛 Account Deletion 500 Error Fix
- **Safe Authentication Guard Logout:** Fixed a 500 Server Error (`BadMethodCallException: Method Illuminate\Auth\RequestGuard::logout does not exist`) when pilots delete their account by checking guard statefulness and explicitly targeting `Auth::guard('web')->logout()`.
- **Session & Pivot Cleanup:** Added explicit session invalidation (`request()->session()->invalidate()`, `regenerateToken()`) and `UserAirline` pivot cleanup during pilot account deletion.

---

## [v1.1.28] - 2026-09-08

### 📸 Authentic Live Site Screenshots (No AI Mockups)
- **Complete AI Image Removal:** Removed all legacy AI-generated `.jpg` previews from `public/images/screenshots/` and codebase.
- **Authentic Live UI Screenshots:** Embedded authentic PNG screenshots captured directly from the live `https://v-airops.artmex-hosting.com` application (Operations Dashboard, Pilot Roster Statistics, 3D Interactive Map) on the homepage.
- **Selective Anonymization:** Applied precise Gaussian blur over personal account names and header menus while keeping all real application UI metrics, fleet counts, and routes crisp and clear.

---

## [v1.1.27] - 2026-09-07

### 🔒 Screenshot Anonymization & Privacy Protection
- **Personal Identifier Protection:** Processed all live application screenshots on the public homepage to blur personal names, user profile header menus, and pilot identity details while maintaining clear visibility of platform UI metrics, fleet statistics, and interactive maps.

---

## [v1.1.26] - 2026-09-07

### 📸 Authentic Live Site Screenshots
- **Live System Authentication & Capture:** Authenticated into the live production environment (`https://v-airops.artmex-hosting.com`) to capture authentic UI screenshots directly from the live application.
- **Operations Console Dashboard:** Updated homepage hero preview with live Operations Dashboard screenshot (`public/images/screenshots/console-dashboard.png`).
- **Pilot Roster & Performance Analytics:** Updated pilot roster section with live Pilot Statistics & Logbook screenshot (`public/images/screenshots/pilot-portal.png`).
- **Live Radar Map Tracking:** Updated flight monitoring section with live 3D Route Radar Map screenshot (`public/images/screenshots/live-radar-map.png`).

---

## [v1.1.25] - 2026-09-07

### 🖼️ Real Application Screenshots on Homepage
- **Application UI Screenshots:** Embedded real application screenshots of the V-Air Ops platform across the public homepage (`welcome.blade.php`), replacing CSS mockups and abstract vectors.
- **Operations Console Preview:** Embedded high-resolution UI screenshot of the main **Operations Dashboard** (`public/images/screenshots/console-dashboard.jpg`).
- **Pilot Roster & Profile Portal:** Embedded high-resolution UI screenshot of the **Pilot Portal** (`public/images/screenshots/pilot-portal.jpg`).
- **Live ACARS Radar Map:** Embedded high-resolution UI screenshot of the **Live ACARS Radar** map interface (`public/images/screenshots/live-radar-map.jpg`).

---

## [v1.1.24] - 2026-09-07

### 🏛️ Professional Homepage & Neutral Copy Refactoring
- **Removal of Hardcoded Fake Statistics:** Replaced all hardcoded fake stats (`1,420+ Active Flights`, `38,500+ Registered Pilots`, `1.8M+ Block Hours`, `128 Flights in Air`) with a clean 4-pillar system capability grid (Multi-Simulator, SimBrief OFP, vPilot ACARS, Multi-Tenant Architecture) modeled after modern virtual airline management platforms.
- **Professional & Neutral Copywriting:** Eliminated marketing hyperbole ("100% Free Forever", "PMDG Ready") in favor of clear, technical aviation descriptions.
- **Refactored Pilot & Flight Metrics:** Replaced gamer slang ("Butter Average") with standard technical terms (*"Touchdown Sink Rate (-FPM) Analysis"*).
- **Streamlined Graphics & Animations:** Removed sci-fi radar sweep animations (`animate-radar-sweep`) and gamer badges in favor of clean operations console previews.

---

## [v1.1.23] - 2026-09-07

### ⚖️ Legal & Support Pages
- **Dedicated Legal Pages:** Created responsive, dark-themed legal pages for **Terms of Service** (`/terms`), **Privacy Policy** (`/privacy`), and **Security Policy** (`/security`) matching V-Air Ops platform branding.
- **Comprehensive Policy Content:** Detailed non-commercial flight sim usage, ACARS telemetry processing, GDPR data subject rights, TLS encryption, tenant isolation, and responsible vulnerability disclosure.
- **Homepage Footer Link Updates:** Updated `Legal & Support` links on the landing page (`welcome.blade.php`) to point to the new named routes (`terms.show`, `policy.show`, `security.show`).

---

## [v1.1.22] - 2026-09-07

### 📈 Google Analytics Integration
- **Google tag (gtag.js) Deployment:** Integrated official Google Analytics tag (`G-52RCD5KV68`) across all web views, including landing page, portal application layout, guest layouts, authentication, and onboarding flows.
- **Blade Component Architecture:** Created `<x-google-analytics />` component with configurable `services.google.analytics_id` environment support (`GOOGLE_ANALYTICS_ID`).
- **Cookie Consent Integration:** Gracefully coordinates with the platform's cookie consent manager (`vops_cookie_consent`), respecting telemetry and analytics preferences in accordance with GDPR requirements.

---

## [v1.1.21] - 2026-09-07

### 📦 Resources Menu & vPilot ACARS Client Download
- **New Resources Sidebar Menu:** Added a dedicated `Resources` menu in the pilot navigation sidebar with an Alpine.js collapsible sub-menu.
- **vPilot ACARS Download Sub-Menu:** Added `Download vPilot ACARS` sub-menu link pointing directly to the latest released version on Gitea (`https://gitea.artmex-hosting.com/tathar26/vops-acars/releases/latest`).
- **Configurable Resource Route:** Added `/resources/vpilot-acars` named route backed by `config('services.vpilot_acars.releases_url')` for clean URLs and flexible configuration.
- **Landing Page Integration:** Added direct download links to the vPilot ACARS client on the public homepage (`welcome.blade.php`) in the Live ACARS Radar section and footer.

---

## [v1.1.20] - 2026-09-07

### 🛠️ Fix VATSIM Pre-Filing 500 Error
- **Direct Official SimBrief VATSIM Pre-file Link:** Switched to directly returning SimBrief's official, battle-tested pre-file link (`$sb['prefile']['vatsim']['link']`), which properly passes the clean, single-line ICAO flight plan string (`raw`) and fuel endurance (`fuel_time`).
- **Eliminated URL Parameter Conflicts:** Removed the conflicting multi-parameter query string (containing conflicting duplicate keys, unaccepted formatting, and raw newline `%0A` characters) that triggered an unhandled exception (`500 Server Error`) in VATSIM's backend parser.
- **Clean Fallback Generator:** For bookings prior to SimBrief generation, restored the clean, validated parameter baseline (`callsign`, `aircraft`, `dep`, `arr`, `alt`, `route`, `altitude`, `tas`, `deptime`, `enroute`, `fuel`, `remarks`) without unhandled query parameters.

---

## [v1.1.19] - 2026-09-07

### 🗺️ Route Map Waypoints & VATSIM Pre-File Completion
- **Intermediate Route Waypoints on Dispatch Map:** Fixed waypoint extraction from SimBrief API v2 JSON payloads (`navlog` array format), allowing the route polyline to accurately trace through all SIDs, airways, navigation fixes, and STARs instead of a single straight line.
- **Top of Climb (TOC) & Top of Descent (TOD) Badges:** Distinctly styled `TOC` (emerald badge) and `TOD` (amber badge) markers along the route with altitude tooltips and flight level popups.
- **Waypoints Hover Tooltips & Details:** Added hover tooltips with waypoint identifiers and clickable popups displaying fix name, altitude, airway, and flight stage.
- **Watermark-Free Basemap Tiles:** Updated Leaflet tile layer to fall back to clean ESRI World Dark Gray Canvas when `CARTO_API_KEY` is not provided, completely removing the "API KEY REQUIRED" watermark from the dispatch map banner.
- **Comprehensive VATSIM Pre-Filing:** Fully populated all VATSIM flight plan form fields (`https://my.vatsim.net/pilots/flightplan`), including:
  - Wake Turbulence Category (`wake_category`, `wake`, `wtc`, `wakeTurbulence`): `M` (Medium), `H` (Heavy), `J` (Super), `L` (Light) auto-resolved from SimBrief equipment category and ICAO airframe specifications.
  - Aircraft Equipment (`equipment`, `equip`) and Transponder (`transponder`, `trans`).
  - Flight Rules (`IFR`), Flight Type (`S`), Off-Block UTC time, Cruise Altitude, Airspeed (TAS), Enroute Time (EET), and Fuel Endurance.
  - Full ICAO Flight Plan raw string (`raw`) and Section 18 / Dispatcher remarks.
  - Persons On Board (`pob`, `souls`).
- **Direct IVAO & POSCON SimBrief Links:** Directly utilizes official pre-file links provided in the SimBrief OFP payload when available (`$sb['prefile']['ivao']['link']` and `$sb['prefile']['poscon']['link']`).
- **Detailed Navlog Param in SimBrief Options:** Added `navlog=1` and `detailed_navlog=1` to the SimBrief custom dispatch popup parameters to ensure future generated OFPs always include comprehensive waypoint data.

---

## [v1.1.18] - 2026-09-07

### 🛠️ Fix Dispatch Action Route Names
- **Resolved RouteNotFoundException:** Fixed undefined route references `pilot.flights` and `pilot.pireps.create` in the Booking Actions sidebar on the dispatch page by redirecting to `flight-centre.index` (for additional flight booking) and `profile.pireps` (for manual PIREPs).

---

## [v1.1.17] - 2026-09-07

### 🛠️ Fix Multiple Root Elements in Dispatch View
- **Resolved Livewire Multiple Root Elements Exception:** Moved Leaflet CSS stylesheet into `<head>` in `layouts/app.blade.php` and removed the `@push('scripts')` block containing `<link>` from `dispatch.blade.php`, ensuring Livewire's DOM parser detects strictly one root HTML element.
- **Sanitized Monospace OFP Output:** Prioritized `plan_text` for monospaced OFP viewing to avoid any potential external HTML fragments breaking the component's DOM tree.

---

## [v1.1.16] - 2026-09-07

### ✈️ Redesigned Flight Dispatch Console & Modern Deck Layout
- **Deck-Inspired Layout & Interactive Route Map:** Completely redesigned the dispatched flight view (`/profile/dispatch/{booking}`) with a full-width Leaflet route map featuring Carto dark matter tiles, route polyline, waypoint markers, and departure/arrival indicators, alongside a flight start expiry countdown notice.
- **Flight Information & METAR Briefing:** Added comprehensive flight identification, departure and arrival airports with visual flight vector, STD/STA/ETD/ETA times, distance, live METARs with quick-access links to Airport Information and METAR-TAF.com.
- **Pilot Information Deck:** Displays assigned aircraft registration, type, Cost Index, Flight Level, passenger load, luggage, freight, route type, network badge, copyable route string, and dispatcher remarks.
- **SimBrief OFP Summary Accordion:** Integrated structured OFP metrics including runway surface conditions and headwind/crosswind components, comprehensive Takeoff & Landing Report (TLR) with V-speeds (V1, VR, V2), flap settings, de-rate/flex temperatures, bleeds and anti-ice, performance profiles, and a 12-item fuel planning matrix.
- **Raw Monospace OFP Viewer:** Retained full official SimBrief OFP monospaced document viewer with instant copy text, print, and external SimBrief link capabilities.
- **Multi-Network Pre-Filing Integration:** Added one-click pre-filing support for **VATSIM** (`https://my.vatsim.net/pilots/flightplan`), **IVAO** (`https://fpl.ivao.aero/create`), and **POSCON** (`https://hq.poscon.net/`) with pre-populated aircraft, route, speed, altitude, endurance, and remarks.
- **SimBrief FMS Flight Plan Downloads:** Provided direct in-app downloading for all SimBrief aircraft flight plans (MSFS `.pln`, PMDG `.rte`, Fenix A320 XML, X-Plane 11/12 `.fms`, PDF briefing, etc.).
- **Route Historical Benchmarks (Compare):** Benchmarks active flight against previous flights on the same city pair, comparing landing rate, fuel burn, flight time, pilot points, passengers, and freight.
- **Virtual Airline Branding Alignment:** Engineered with full tenant custom variable support (`var(--tenant-card-bg)`, `var(--tenant-accent)`, `var(--tenant-card-text)`, etc.) to preserve airline branding across themes.

---

## [v1.1.15] - 2026-09-07

### ✈️ SimBrief OFP Layout & Alternate Airports Fix
- **Dynamic OFP Layout Extraction:** Fixed SimBrief OFP format detection by reading from `params.ofp_layout` (which contains the true airline layout generated on SimBrief, such as `EZY`, `RYR`, `BAW`, `DLH`), preventing the layout from reverting to the default `LIDO`.
- **Accurate Alternate Airport Parsing:** Fixed alternate airport extraction from SimBrief API v2 JSON payloads by supporting numerically indexed arrays (`alternate[0]`, `alternate[1]`), single objects, and distinct alternate keys.
- **Removed Hardcoded German Fallbacks:** Removed all hardcoded fallbacks to `EDDW` (Bremen) and `EDHL` (Lübeck) across the dispatch view and briefing cards, displaying the actual primary and secondary alternate airports (or `None` when none are configured).
- **Auto-Healing for Existing Bookings:** Dispatched bookings loaded in `Dispatch::mount()` automatically normalize layout and alternate keys from their stored SimBrief payloads.

---

## [v1.1.14] - 2026-09-07

### 🗺️ PIREP Map & Chart Fixes
- **Resolved Fatal Module Syntax Error:** Fixed `Uncaught SyntaxError: Cannot use 'import.meta' outside a module` on the PIREP details dashboard by removing `import.meta` evaluation from the inline `<script>` tag.
- **Restored Leaflet Map & Altitude Profile Chart:** Elimination of the script parse error allows Alpine's `pirepDetailDashboard` component to register cleanly, restoring the interactive flight map and telemetry profile graphs.

---

## [v1.1.13] - 2026-09-07

### 📊 Pilot Statistics & Logbook Overhaul
- **Reliable Callsign Prefixes:** Fixed callsign extraction to inspect flight logs, ATC callsigns, and route identifiers. Handles 2-4 letter ICAO airline designators (e.g. `EZY`, `SVK`, `BAW`) and resolves single-letter anomalies (such as easyJet `U2` flights being mapped to `EZY`).
- **Network Aggregation & Persistence:** Persisted the flown network (`VATSIM`, `IVAO`, `POSCON`, or `Offline`) on PIREP filing and automatically backfilled existing flights from SimBrief payloads and pilot preferences, eliminating `"Unknown"` network slices.
- **Astronomical Day/Night Calculation Engine:** Implemented high-precision astronomical day/night calculations for both takeoffs and landings using `date_sun_info()`, airport coordinates, and departure/arrival timestamps (with regional solar longitude fallback), replacing hardcoded daytime defaults.
- **Simulator Name Normalization:** Consolidated fragmented simulator strings (`MSFS`, `Microsoft Flight Simulator (MSFS)`, and unpopulated values) into standard canonical categories (`MSFS 2020`, `MSFS 2024`, `X-Plane 12`, `Prepar3D`, `FSX`).
- **Modernized Landing Rate Chart:** Redesigned the touchdown rate chart into a continuous line graph with smooth tension curves (`tension: 0.3`), transparent gradient area fill, styled points with hover effects, custom tooltip formatting (`Touchdown: -X FPM`), and unique chronologically ordered date/callsign labels.
- **Logbook Passenger, Freight & Fuel Data:** Fixed per-aircraft logbook metrics by persisting and backfilling passenger counts and freight/cargo from SimBrief and ACARS telemetry. Unified fuel usage calculations across rows and totals using `sum('fuel_used') ?: sum('block_fuel')`.
- **Instant Statistics Auto-Refresh:** Added synchronous statistic recalculation upon visiting `/profile/statistics` along with a dedicated "Refresh Statistics" action with loading feedback.

### 📡 vPilot ACARS Telemetry Enhancements
- **Extended PIREP Payload:** Upgraded `vpilot-acars` to transmit `network`, `passengers`, `cargo_kg`, and precise `block_off_time` / `block_on_time` strings with every submitted PIREP.
- **Dispatch OFP Metadata Retention:** Desktop client retains flight network and passenger/cargo loads from active booking dispatches, ensuring seamless end-to-end synchronization.

---

## [v1.1.12] - 2026-09-07

### ✈️ SimBrief Profile Integration & Dispatch Overhaul
- **Live SimBrief Airframe Profiles:** Integrated SimBrief's dynamic airframe database (`https://www.simbrief.com/api/inputs.airframes.json`) via `SimBriefService::getAirframesForType()`. Automatically fetches and caches accurate add-on airframe profiles (e.g. Fenix A319/A320/A321 CFM/IAE, FlyByWire A320neo, ToLiss, iniBuilds, PMDG 737/777) matching the airframe's aircraft type.
- **Direct SimBrief Profile Dispatch:** Selecting an add-on airplane profile passes its exact `airframe_internal_id` as the `type` parameter to SimBrief's custom dispatch URL, instantly loading the tailored weights, equipment codes, and fuel factors in SimBrief.
- **Streamlined Dispatch UI:** Removed the redundant blue SimBrief sync box on the flight dispatch page, reorganizing the top configuration into a responsive 3-column grid (**Aircraft**, **SimBrief Airplane Profile**, and **SimBrief OFP Format / Layout**).
- **SimBrief ID Validation Alert:** Added immediate validation when toggling "Dispatch via SimBrief". If the pilot has not configured their SimBrief Username or Pilot ID, the toggle resets and presents an alert linking directly to Pilot Preferences.
- **Intelligent Auto-Alternates & Auto-Load:** When "Auto-find Alternates" is enabled, alternate inputs are disabled and left empty so SimBrief automatically determines the best alternates. Passenger and hold luggage counts default to empty, passing `pax=AUTO` and `cargo=AUTO` to SimBrief with quick-clear buttons to return to automated load calculations at any time.

### 🛠️ Bug Fixes & Reliability
- **PIREP Chart & Map Initialization:** Resolved race conditions and canvas reuse errors in `pirepDetailDashboard` by implementing guaranteed dependency loaders for Leaflet and Chart.js, waiting for Alpine DOM readiness (`$nextTick`), safely destroying prior Chart instances, and separating map and altitude profile chart rendering into isolated `try/catch` blocks.

---

## [v1.1.11] - 2026-09-06

### 🛠️ Bug Fixes & Improvements
- **Arrived Flight Aircraft Resolution:** Fixed an issue in `LiveFlightService` where completed/arrived flights defaulted to `Boeing 737-800 - G-DEMO` due to the booking record being deleted upon PIREP filing. Added automatic resolution from recent tenant `Pirep` records, airframe registrations, and dynamic database `AircraftType` lookups.
- **PIREP Details View Script & Map Fix:** Resolved a blade template escaping issue where inline JavaScript attributes caused unescaped quotes to break HTML parsing and render raw JavaScript code onto the PIREP review page. Extracted component logic into a dedicated `Alpine.data('pirepDetailDashboard')` definition to ensure reliable Leaflet map and Chart.js profile rendering.

---

## [v1.1.10] - 2026-09-04

### 🛠️ Bug Fixes & Improvements
- **Aircraft Type Code Resolution & Truncation Protection:** Implemented `resolveAircraftTypeCode()` in `ScheduleImportService` to automatically sanitize and map verbose fleet database airframe descriptions (such as `AIRBUS A320-214 (SL)`) down to authentic 2-4 character ICAO type codes (`A320`, `A20N`, `B738`, etc.).
- **Database Schema Column Safety:** Strictly bounded aircraft type code to $\le 10$ characters, airframe registration to $\le 20$ characters, and names to $\le 255$ characters, preventing MySQL `String data, right truncated: 1406 Data too long for column 'code'` errors.
- **Fleet Manager Table Badges:** Updated the preview table to display normalized ICAO type badges for clean presentation.

---

## [v1.1.9] - 2026-09-04

### ✈️ Live Airline Fleet & Airframe API Import
- **Central Worldwide Fleet Database Integration:** Connected V-Ops directly to the microservice fleet endpoints (`/api/fleet/{operator_icao}`, `/api/aircraft/{registration}`, `/api/hex/{icao24}`, `/api/fleet-stats`) utilizing existing microservice authentication (`SCHEDULES_API_KEY`).
- **Fleet Manager Livewire Interface:** Upgraded the Import Airframes modal with a dedicated *Live Airline Fleet API (OpenSky)* tab. Supports querying by airline 3-letter ICAO, real-time in-memory filtering, multi-selection, and automatic detection of airframes already in the fleet (`In Fleet` vs `New`).
- **Automatic Aircraft Type Resolution:** Importing airframes automatically resolves or creates missing `AircraftType` records based on the airframe's ICAO type designator (e.g. `A20N`, `B738`) and manufacturer/model metadata.
- **Artisan Console Command:** Added `php artisan fleet:import {--tenant=} {--operator=} {--limit=2000}` for command-line and automated background synchronization of airline fleets.
- **Navigation & Cross-Links:** Added quick navigation between Global Airline Schedules and Fleet & Airframe Import.

---

## [v1.1.8] - 2026-09-04

### 🗺️ CARTO Basemaps API Key Support
- **CARTO API Key Integration:** Added `CARTO_API_KEY` configuration across `.env`, `config/services.php`, and Docker Compose files (`docker-compose.prod.yml`, `docker-compose.staging.yml`, `docker-compose.local.yml`).
- **Leaflet Map Integration:** Updated live operations radar (`live-flight-map.js`), flight booking/network routes (`flight-map.js`), and PIREP review telemetry map (`pirep-detail-view.blade.php`) to dynamically append `?key=...` to CARTO Dark Matter raster tiles to remove the API key requirement watermark.
- **Runtime Metadata Delivery:** Injected the key via `<meta name="carto-api-key">` and `window.CARTO_API_KEY` in `app.blade.php` and `guest.blade.php` to allow runtime configuration without rebuilding Docker images.

---

## [v1.1.7] - 2026-09-04

### 📦 Maintenance & Version Synchronization
- **Version Alignment:** Synchronized default application configuration and `VersionService` fallback to `v1.1.7`.
- **Production Build:** Triggered production container build pipeline and release image tagging.

---

## [v1.0.9] - 2026-08-19

### 🚀 New Features & Enhancements

- **Live Operations Flight Map (Radar View):**
  - Integrated a real-time dark-themed flight radar map powered by Leaflet and CartoDB Dark Matter tiles.
  - Aircraft markers rendered with SVG silhouettes **dynamically rotated by their exact heading (`heading_deg`)**.
  - Route flight paths connecting Departure Airport, Aircraft live position, and Arrival Airport.
  - Interactive click-to-focus allowing users to click any flight in the table to smoothly center and fly to that aircraft.
  - Preserved map position and zoom levels during periodic updates and background polling. Added on-demand "Reset View" button.

- **Aircraft Hover Tooltip Card:**
  - Interactive frosted-glass telemetry card on aircraft hover showing:
    - Callsign & Flight Number (e.g., `EZS508HZ` / `DS101`)
    - Pilot Name & Callsign (e.g., `Glenn Satory (EZY1013)`)
    - Route Origin & Destination (e.g., `EGKK ┄✈┄ LFLL`)
    - Aircraft Type & Airframe Registration (e.g., `Boeing 737-800 - G-DEMO`)
    - Altitude / Flight Level (e.g., `FL350` / `35,000 ft`)
    - Flight Phase Status (e.g., `Cruising`, `Climbing`, `Descending`, `Preflight`, `Landed`)
    - Ground Speed (kts) and Heading (°)
    - Flight Network (`VATSIM`, `IVAO`, `Offline`, `POSCON`)

- **Active Flights Table:**
  - Standardized live flights table placed directly below the radar map.
  - Displays live active flight count badge, Zulu timestamp (`Updated: HH:MMz`), and manual refresh button.
  - Complete columns: `PILOT`, `CALLSIGN`, `DEPARTURE`, `ARRIVAL`, `AIRCRAFT`, `ETE/ETD`, `DISTANCE`, `STATUS`, `NETWORK`, and `ACTION`.

- **Multi-Airline ICAO Configuration (VA Settings):**
  - Enabled virtual airline owners to define both a **Primary Airline ICAO** (e.g. `EZY`) and multiple **Secondary Airline ICAOs** (e.g. `EZS` easyJet Switzerland, `EJU` easyJet Europe).
  - Dynamic UI for adding and removing secondary ICAO tags with duplicate detection and uppercase normalization.

- **Route Manager Callsign & Flight Number System:**
  - Added dedicated **Flight Number** textfield (supporting commercial/IATA identifiers like `U28161`, `FR2605`, `1181`).
  - Added **Callsign ICAO Prefix Dropdown** (populated with all primary + secondary airline ICAOs) and **Callsign Suffix Textfield** (e.g. `508HZ` or `8161`).
  - Real-time callsign preview pill badge (`EZS` + `508HZ` => `EZS508HZ`).
  - Route table updated to display both commercial Flight Number and ATC Callsign clearly.

- **SimBrief Dispatch Alignment:**
  - Automated parsing of commercial IATA airline codes (e.g. `U2` from `U28161`, `FR` from `FR2605`) and numeric flight number digits (`fltnum`) for SimBrief flight planning.
  - Passes the exact ICAO ATC telephony callsign (e.g. `EZS508HZ`) to SimBrief.
  - Auto-cleans active bookings on PIREP submission and cancellation while **preserving all position reports** for flight review.

---
