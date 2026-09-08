# V-Air Ops — Airline Management & Staff Operations Manual

*Last Updated: September 2026 | Platform Version: v1.1.31*

This guide provides virtual airline owners, operations managers, fleet dispatchers, and staff members with complete documentation on configuring, managing, and maintaining a Virtual Airline on the **V-Air Ops** platform.

---

## 📑 Manual Sections

1. [Airline Configuration & Branding](#1-airline-configuration--branding)
2. [Hub & Airport Management](#2-hub--airport-management)
3. [Fleet & Airframe Administration](#3-fleet--airframe-administration)
4. [Routes, Schedules & Global Imports](#4-routes-schedules--global-imports)
5. [Rank Structures & Promotion Criteria](#5-rank-structures--promotion-criteria)
6. [PIREP Review & Safety Enforcement](#6-pirep-review--safety-enforcement)
7. [NOTAM Operations System](#7-notam-operations-system)
8. [Multi-Tenant Role-Based Access Control (RBAC)](#8-multi-tenant-role-based-access-control-rbac)

---

## 1. Airline Configuration & Branding

As a VA Owner or Administrator:
1. Navigate to **Administration > Airline Settings**.
2. Configure basic airline metadata:
   - **Airline Name & Callsign**: Standard 3-letter ICAO code and telephony callsign (e.g. `VAIR` / `V-AIR`).
   - **Visual Branding**: Upload high-resolution airline logos, square app icons, and custom banner imagery.
   - **Default Operational Rules**: Set minimum landing rates, maximum G-forces, auto-approval thresholds, and default SimBrief OFP formats.

---

## 2. Hub & Airport Management

Virtual airlines operate from defined hub airports:
- **Base Hub**: Mark your primary operating base (e.g. `EGLL` - London Heathrow).
- **Secondary Hubs & Focus Cities**: Add operating bases with defined gates and hub coordinators.
- **Runway & Airport Data**: V-Air Ops maintains a global database of ICAO codes, runway headings, lengths, surface types, and elevations for automated distance and navlog calculation.

---

## 3. Fleet & Airframe Administration

### Registering Airframes
1. Navigate to **Fleet Management > Add Airframe**.
2. Define airframe parameters:
   - **ICAO Type Code**: e.g. `A320`, `B738`, `B77W`, `A359`.
   - **Tail Registration Number**: e.g. `G-VAIR`, `N801VA`.
   - **Cabin & Cargo Limits**: Maximum passenger capacity and cargo capacity in kilograms or pounds.
   - **Fuel Capacity & Burn Rates**: Standard fuel capacities for SimBrief dispatch calculations.
   - **Current Location**: Initial airport where the airframe is stationed.

### Fleet Relocation & Status
- Track live airframe locations as pilots fly legs across the network.
- Mark aircraft as *Active*, *Under Maintenance (AOG)*, or *Retired*.

---

## 4. Routes, Schedules & Global Imports

### Manual Route Creation
1. Navigate to **Schedules > New Route**.
2. Enter Flight Number (e.g. `VA101`), Departure ICAO, Arrival ICAO, Scheduled Departure Time (UTC), and Scheduled Arrival Time.
3. Assign authorized airframe types and standard route waypoints (e.g. SID, enroute airway sequence, STAR).

### Bulk Imports & AirLabs Integration
- **CSV Bulk Import**: Upload spreadsheet schedules containing hundreds of seasonal legs with automated duplicate and syntax validation.
- **AirLabs Real-World Sync**: Synchronize live commercial flight schedules directly into your virtual airline with automated ICAO mapping.

---

## 5. Rank Structures & Promotion Criteria

Configure progressive pilot career tracks under **Administration > Ranks**:
- **Flight Hour Thresholds**: Minimum total hours required for each rank (e.g., *Cadet: 0h*, *Second Officer: 25h*, *First Officer: 100h*, *Captain: 250h*, *Senior Captain: 500h*).
- **Point Multipliers**: Bonus points awarded per flight hour and per soft landing.
- **Aircraft Type Type-Ratings**: Restrict long-haul widebodies (e.g. B777, A350) to Captain rank and above.
- **Automatic vs. Manual Promotions**: Choose whether promotions occur automatically upon PIREP approval or require staff sign-off.

---

## 6. PIREP Review & Safety Enforcement

### Review Queue
Under **Flight Operations > Review PIREPs**:
- **Automated Validation**: PIREPs meeting all criteria (touchdown rate within limits, valid route flown, no severe overspeed) can be auto-approved based on your airline's policy.
- **Manual Review**: Review flights flagged for:
  - Hard landings (e.g. `> -400 FPM`).
  - G-force exceedances (`> 2.2G`).
  - Route deviation or anomalous flight durations.
- **Actioning**: Click **Accept** to award hours and points, or **Reject** with a custom feedback note to the pilot.

---

## 7. NOTAM Operations System

Publish critical operational notices to your pilots under **Operations > NOTAMs**:
- **Priority Levels**:
  - `High`: Immediate operational hazard or mandatory event notice.
  - `Medium`: Route changes, hub construction, or simulator compatibility updates.
  - `Low`: General community guidelines or pilot notices.
- **Tracking Acknowledgments**: Track which pilots have read and acknowledged the NOTAM. Unread high-priority NOTAMs can trigger alerts on the pilot dispatch board.

---

## 8. Multi-Tenant Role-Based Access Control (RBAC)

V-Air Ops provides airline-scoped role management under **Administration > Staff Roles**:
- **Airline Roles**: Create custom roles such as *Operations Director*, *Fleet Chief*, *Flight Dispatcher*, *Event Coordinator*, or *Academy Instructor*.
- **Granular Permissions**:
  - `view_fleet`, `edit_fleet`, `manage_aircraft`
  - `view_schedules`, `create_routes`, `import_schedules`
  - `review_pireps`, `approve_pireps`, `delete_pireps`
  - `manage_notams`, `publish_announcements`
  - `manage_users`, `assign_roles`
- **Global System Admin Override**: Master Administrators maintain universal maintenance access across all multi-tenant airlines.
