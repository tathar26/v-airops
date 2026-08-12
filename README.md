# V-Ops

V-Ops is a comprehensive Virtual Airline Operations platform designed for flight simulation communities. It provides a robust, multi-tenant environment for managing virtual airlines, tracking pilot statistics, processing PIREPs (Pilot Reports), and visualizing flight data. 

Built on the Laravel framework, V-Ops emphasizes performance, reliability, and modern web architecture.

## Deployment Types

V-Ops is fully containerized and uses Gitea Actions to automatically build and distribute Docker images to Docker Hub. We provide several Docker Compose configurations to suit different environments.

### 1. Local Development (`docker-compose.local.yml`)
The local development environment uses Laravel Sail. This configuration is optimized for active development, providing live reloading and direct access to the application code via volume mounts.
- **Usage**: `docker compose -f docker-compose.local.yml up -d`

### 2. Staging Environment (`docker-compose.staging.yml`)
The staging environment runs the latest development build (`devel` tag) from the `main` branch. It simulates a production-like environment for testing new features and ensuring stability before a full release.
- **Image**: `tathar26/v-ops:devel`
- **Usage**: `docker compose -f docker-compose.staging.yml up -d`

### 3. Production Environment (`docker-compose.prod.yml`)
The production environment is built for stability and performance. It runs the official release builds (Semantic Versioning tags) and runs with optimizations enabled (e.g., cached configurations, disabled debug mode).
- **Image**: `tathar26/v-ops:latest` (or a specific version tag like `1.2.3`)
- **Usage**: `docker compose -f docker-compose.prod.yml up -d`

## Demo Accounts

To access the demo data seeded in the application, you can use the following accounts (all passwords are `password`):

- **System Admin**: `admin@vops.test` (Master Admin)
- **VA Owner**: `owner@demo.vops.test` (VA Owner)
- **Test Pilot**: `pilot@demo.vops.test` (Pilot)

## License

V-Ops is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
