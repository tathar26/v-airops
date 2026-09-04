# Configuration & Environment Variables Reference

An exhaustive guide to all configuration options and environment variables used by the **V-Air Ops** platform.

---

## 1. ⚙️ Application & Core Settings

| Variable | Default | Type | Description |
|---|---|---|---|
| `APP_NAME` | `V-Air Ops` | String | The official title displayed across the UI, email templates, and page headers. |
| `APP_ENV` | `production` | String | Application environment (`production`, `staging`, `local`). Controls debug error detail and strict caching. |
| `APP_KEY` | *None* | String (Base64) | 32-byte encryption key used for secure session encryption, cookies, and tokens. Generate with `php artisan key:generate`. |
| `APP_DEBUG` | `false` | Boolean | Set to `false` in production. If `true`, full stack traces and sensitive database credentials can be exposed on errors. |
| `APP_URL` | `http://localhost` | URL | The full canonical public URL (e.g. `https://vops.yourdomain.com`). Used for link generation and API callbacks. |
| `APP_LOCALE` | `en` | String | Primary language locale (`en`, `de`, `fr`, `es`, `nl`). |
| `APP_FALLBACK_LOCALE` | `en` | String | Fallback language locale if string translation is missing. |
| `FORCE_HTTPS` | `false` | Boolean | When set to `true`, forces all generated asset and action URLs to use the `https://` protocol. |
| `APP_VERSION` | *Auto (Git)* | String | Optional manual override for the version tag displayed in the sidebar footer. Defaults to the active Git release tag. |

---

## 2. 🗄️ Database Configuration

| Variable | Default | Type | Description |
|---|---|---|---|
| `DB_CONNECTION` | `mysql` | String | Database driver (`mysql`, `mariadb`, `sqlite`, `pgsql`). |
| `DB_HOST` | `127.0.0.1` | String | Hostname or IP of the MariaDB/MySQL server (or `mariadb` in Docker). |
| `DB_PORT` | `3306` | Integer | Database connection port. |
| `DB_DATABASE` | `vops_prod` | String | Database name. |
| `DB_USERNAME` | `vops` | String | Database authentication username. |
| `DB_PASSWORD` | *None* | String | Database authentication password. |
| `DB_ROOT_PASSWORD` | *None* | String | MariaDB administrative root password (used during Docker provisioning). |

---

## 3. 🚀 Cache, Session & Queue Drivers

| Variable | Default | Type | Description |
|---|---|---|---|
| `CACHE_STORE` | `redis` | String | Cache driver (`redis`, `database`, `file`, `array`). Redis is strongly recommended for multi-user performance. |
| `SESSION_DRIVER` | `redis` | String | Session storage driver (`redis`, `database`, `file`, `cookie`). |
| `SESSION_LIFETIME` | `120` | Integer | Session expiration time in minutes. |
| `QUEUE_CONNECTION` | `redis` | String | Background queue driver (`redis`, `database`, `sync`). Use `redis` for non-blocking aviation imports and ACARS ingestion. |
| `REDIS_CLIENT` | `phpredis` | String | Redis PHP driver (`phpredis` or `predis`). |
| `REDIS_HOST` | `127.0.0.1` | String | Redis host (`redis` inside Docker). |
| `REDIS_PORT` | `6379` | Integer | Redis port. |
| `REDIS_PASSWORD` | `null` | String | Optional Redis authentication password. |

---

## 4. ✉️ Email & Notification Services

| Variable | Default | Type | Description |
|---|---|---|---|
| `MAIL_MAILER` | `smtp` | String | Mail driver (`smtp`, `sendmail`, `log`, `ses`, `mailgun`). |
| `MAIL_HOST` | `smtp.mailgun.org` | String | Outbound SMTP server hostname. |
| `MAIL_PORT` | `587` | Integer | Outbound SMTP port (`587` for TLS, `465` for SSL, `25`). |
| `MAIL_USERNAME` | *None* | String | SMTP account username. |
| `MAIL_PASSWORD` | *None* | String | SMTP account password or API token. |
| `MAIL_ENCRYPTION` | `tls` | String | Transport encryption protocol (`tls` or `ssl`). |
| `MAIL_FROM_ADDRESS` | `noreply@yourdomain.com` | Email | Sender email address appearing on verification and approval notifications. |
| `MAIL_FROM_NAME` | `${APP_NAME}` | String | Sender name appearing in recipient inboxes. |

---

## 5. 🌍 Aviation APIs & External Integrations

| Variable | Default | Type | Description |
|---|---|---|---|
| `AIRLABS_API_KEY` | *None* | String | Optional API key from [AirLabs.co](https://airlabs.co). Enables automatic real-world flight number lookups during global network route imports. |
| `SIMBRIEF_API_URL` | `https://www.simbrief.com/api/xml.fetcher.php` | URL | Endpoint for SimBrief OFP and XML flight data fetching. |
| `CARTO_API_KEY` | *None* | String | CARTO Basemaps API key from [carto.com/basemaps/apikey](https://carto.com/basemaps/apikey/). Removes the "API KEY REQUIRED" watermark on Leaflet flight radar, network, and PIREP review maps. |

---

## 6. 📦 Storage & File System (Optional S3 / Cloud Storage)

| Variable | Default | Type | Description |
|---|---|---|---|
| `FILESYSTEM_DISK` | `local` | String | Default storage driver (`local`, `public`, `s3`). |
| `AWS_ACCESS_KEY_ID` | *None* | String | AWS / S3-compatible API Access Key. |
| `AWS_SECRET_ACCESS_KEY` | *None* | String | AWS / S3-compatible API Secret Key. |
| `AWS_DEFAULT_REGION` | `us-east-1` | String | Target cloud bucket region. |
| `AWS_BUCKET` | *None* | String | Cloud storage bucket name. |
| `AWS_ENDPOINT` | *None* | URL | Optional custom S3-compatible endpoint (e.g. MinIO, Cloudflare R2, Wasabi). |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` | Boolean | Set to `true` when connecting to MinIO or custom local S3 storage. |

---

## 7. 🔒 Production Security Example (`.env`)

```ini
APP_NAME="V-Air Ops"
APP_ENV=production
APP_KEY=base64:Xk82mNzP3k...==
APP_DEBUG=false
APP_URL=https://vops.example.com
FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vops_prod
DB_USERNAME=vops_user
DB_PASSWORD="YourStrongSecurePassword123!"

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD="SG.your_sendgrid_key_here"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@vops.example.com"
MAIL_FROM_NAME="V-Air Ops Dispatch"

AIRLABS_API_KEY="your-airlabs-token"
```
