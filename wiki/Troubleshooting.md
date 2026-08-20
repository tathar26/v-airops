# Troubleshooting & Maintenance Guide

A hands-on operational guide for diagnosing runtime issues, inspecting logs, resolving queue bottlenecks, and performing routine system maintenance for **V-Air Ops**.

---

## 1. 📂 Critical Log Locations

When debugging an issue, always consult the primary log files first:

| Component | Host / Container Path | Description |
|---|---|---|
| **Laravel App Logs** | `v-ops/storage/logs/laravel.log` | Application errors, unhandled exceptions, database query failures, and Livewire stack traces. |
| **Worker Logs** | `v-ops/storage/logs/worker.log` | Output and errors from background queue workers processing import jobs and batch tasks. |
| **Nginx Error Log** | `/var/log/nginx/error.log` (or `docker compose logs app`) | Web server gateway timeouts (504), bad gateway (502), and SSL handshake issues. |
| **PHP-FPM Log** | `/var/log/php8.4-fpm.log` | PHP worker segfaults, memory exhaustion limits, and timeout terminations. |

---

## 2. 🔍 Common Issues & Quick Resolutions

### 1. HTTP 500 Internal Server Error
- **Cause 1: Missing Application Key**
  ```bash
  php artisan key:generate
  ```
- **Cause 2: File Permission Denied on `storage` or `bootstrap/cache`**
  ```bash
  sudo chown -R www-data:www-data storage bootstrap/cache
  sudo chmod -R 775 storage bootstrap/cache
  ```
- **Cause 3: Stale Compiled Views or Config Cache**
  ```bash
  php artisan optimize:clear
  ```

---

### 2. Livewire Component or Blade Syntax Errors
- **Symptom**: Unhandled `ParseError` or unexpected end of file exception after updating code.
- **Resolution**:
  Clear all pre-compiled Blade templates from the framework cache:
  ```bash
  php artisan view:clear
  php artisan cache:clear
  ```

---

### 3. Background Jobs / Global Imports Stuck or Not Running
- **Symptom**: Global Network Import progress bar stays at 0% or does not advance.
- **Check Queue Worker Status**:
  ```bash
  # Check Supervisor status (bare metal)
  sudo supervisorctl status vops-worker:*

  # Check Docker queue container
  docker compose -f docker-compose.prod.yml ps queue
  docker compose -f docker-compose.prod.yml logs --tail=50 queue
  ```
- **Inspect and Retry Failed Jobs**:
  ```bash
  # List failed jobs
  php artisan queue:failed

  # Retry all failed jobs
  php artisan queue:retry all

  # Clear obsolete failed jobs
  php artisan queue:flush
  ```

---

### 4. Database Schema Out of Sync or Missing Columns
- **Symptom**: `Column not found` SQL error (e.g. `Unknown column 'callsign_icao' in 'field list'`).
- **Resolution**:
  Run pending migrations safely:
  ```bash
  php artisan migrate --force
  ```
  Check status of all applied migrations:
  ```bash
  php artisan migrate:status
  ```

---

### 5. Telemetry Ingestion / ACARS Connection Issues
- **Check 1: SSL Certificate & Mixed Content**
  Ensure the server has a valid HTTPS certificate.
- **Check 2: `FORCE_HTTPS` Environment Variable**
  Ensure `.env` contains `FORCE_HTTPS=true` if behind a Cloudflare or Nginx reverse proxy so redirect URLs do not downgrade to `http://`.
- **Check 3: Pilot API Token / Active Booking**
  Verify the pilot has an active booked flight in the Flight Centre or valid credentials in their pilot profile.

---

## 3. 🧹 Routine Maintenance Playbook

### Weekly / Monthly Maintenance Commands
```bash
# 1. Prune old batch jobs and failed job records
php artisan queue:prune-batches --hours=48
php artisan queue:prune-failed --hours=168

# 2. Clear expired session records (if using database session driver)
php artisan session:gc

# 3. Optimize MariaDB tables & rebuild indexes
sudo mysqlcheck -u vops -p --optimize --databases vops_prod

# 4. Refresh application cache
php artisan optimize
```

### Log Rotation Configuration
Create `/etc/logrotate.d/v-air-ops`:
```logrotate
/var/www/v-air-ops/v-ops/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
}
```
