# V-Air Ops — Wiki Documentation

*Last Updated: September 2026 | Platform Version: v1.1.31*

Welcome to the official technical documentation, architecture guide, and operational handbook for **V-Air Ops**—the next-generation Virtual Airline Operations SaaS Platform.

---

## 📑 Table of Contents

| Document | Description | Target Audience |
|---|---|---|
| **[Pilot & Dispatcher User Guide](User-Guide.md)** | Step-by-step pilot manual: multi-airline enrollment, flight booking, SimBrief OFP dispatch, live 3D radar tracking, and PIREP scoring. | Pilots, Dispatchers |
| **[Airline Management Manual](Airline-Management.md)** | Comprehensive staff manual: hub creation, fleet airframes, route schedules, ranking rules, NOTAM publishing, and RBAC permissions. | VA Owners, Operations Staff |
| **[Features & System Capabilities](Features.md)** | Detailed breakdown of multi-tenancy, telemetry ingestion, PIREP scoring, schedule management, and dynamic branding. | VA Owners, Staff, Developers |
| **[Deployment Strategies](Deployment.md)** | Bare-metal LEMP setup, Nginx reverse proxy configuration, SSL certificates, and PHP 8.4 optimization. | System Administrators, DevOps |
| **[Docker & Compose Installation](Docker-Install.md)** | Multi-container Docker orchestration (`app`, `scheduler`, `queue`, `mariadb`, `redis`), networking, and volumes. | DevOps Engineers |
| **[Configuration & Environment Variables](Configuration.md)** | Exhaustive reference guide for all `.env` keys, defaults, and security configurations. | Administrators |
| **[Troubleshooting & Maintenance](Troubleshooting.md)** | Log locations, debugging techniques, queue recovery, database migrations, and health checks. | Support, DevOps |

---

## 🧭 Repository Ecosystem Map

The V-Air Ops repository contains the central SaaS platform, background workers, and supporting client components:

```
v-air-ops/
├── v-ops/                       # Core V-Air Ops Web & Operations Platform (Laravel 13 SaaS)
│   ├── app/
│   │   ├── Http/Controllers/    # API & Web Controllers (PIREPs, Flights, ACARS Ingestion)
│   │   ├── Livewire/            # Reactive Hubs (Fleet, Route, Airport, PIREP Managers)
│   │   ├── Models/              # Eloquent Models (Tenant, Route, Airframe, PIREP, Rank)
│   │   ├── Jobs/                # Background Queue Jobs (AirLabs & Global Schedule Importers)
│   │   └── Services/            # SimBrief, FSACARS, & Versioning Services
│   ├── config/                  # Laravel Configuration Files
│   ├── database/migrations/     # Database Schema & Migrations
│   ├── docker/                  # Nginx, PHP-FPM, & Supervisord Container Configs
│   ├── resources/views/         # Blade & Livewire Frontend Templates
│   ├── routes/                  # API, Web, & Console Route Definitions
│   └── docker-compose.*.yml     # Local, Staging, & Production Docker Stacks
│
├── client/                      # Desktop Telemetry Client (Python & PySide6)
├── installer/                   # Windows Package Builder & Installer Scripts
├── scripts/                     # Build, Test, & Packaging Automation Scripts
├── tests/                       # Automated Test Suites
└── wiki/                        # Complete V-Air Ops Technical Documentation Suite
```

---

## ⚙️ Core Architecture Concepts

1. **Multi-Tenancy Isolation**:
   Every airline entity is encapsulated via `App\Models\Tenant`. Models utilizing `App\Traits\BelongsToTenant` automatically scope queries to the authenticated pilot's active airline or session tenant ID, preventing data leakage across airlines.

2. **Asynchronous Processing Engine**:
   Heavy aviation data lookups (such as AirLabs route synchronization and 200,000+ flight global imports) are dispatched to Redis-backed queues and executed across dedicated supervisor worker containers.

3. **Telemetry & PIREP Pipeline**:
   The telemetry ingestion endpoint receives live aircraft state vectors at 1Hz, caching active positions in Redis for real-time fleet radar maps and compiling flight records into precision PIREPs for deterministic touchdown scoring.
