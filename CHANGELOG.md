# Changelog

All notable changes to the Virtual Airline Operations (**V-Ops**) platform are documented in this file.

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

- **ACARS Telemetry & Flight Alignment:**
  - **Instant Synchronous Telemetry Ingestion:** Position pings to `/api/v1/acars/position` immediately persist to `acars_positions` for real-time tracking.
  - **Comprehensive Parameter Normalization:** Automatically resolves payload aliases from ACARS clients (`speed`/`ground_speed_kt`, `lat`/`latitude`, `lon`/`longitude`, `alt`/`altitude_ft`, `hdg`/`heading_deg`, `ias`/`indicated_airspeed_kt`, `vs`/`vertical_speed_fpm`, `phase`/`flight_phase`).
  - **Strict Pilot Flight Ownership:** Enforced authenticated user verification on position pings to guarantee telemetry is strictly attached to the correct pilot and active flight.
  - **Multi-Airline Callsign Authentication:** ACARS login now supports email, username, pilot account callsigns, and airline-specific callsigns (`user_airlines.callsign`).
  - **Accurate SimBrief OFP Extraction:** Real dispatched flight parameters (`callsign`, `departure_icao`, `arrival_icao`, `airframe`, `network`, `distance`, `ete`) are accurately loaded without global scope filtering in stateless API endpoints.

- **Frontend Standardizations & Theme Synchronization:**
  - Navigation sidebar and top header dynamically inherit the Virtual Airline's chosen accent color (`var(--tenant-accent)`).
  - High-contrast typography and theme adaptation across dark and light airline color themes.
  - Relocated KPI statistic cards to the top of the dashboard and live operations radar to the bottom.

---
