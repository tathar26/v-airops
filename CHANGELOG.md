# Changelog

All notable changes to the Virtual Airline Operations (**V-Ops**) platform are documented in this file.

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
  - Standardized vAMSYS-style table placed directly below the radar map.
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
