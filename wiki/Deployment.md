# Production Deployment Guide

This guide covers production deployment strategies for **V-Air Ops**, including bare-metal LEMP setups, systemd/supervisord process management, SSL termination, and caching optimizations.

---

## 1. 🖥️ System Requirements & Prerequisites

### Recommended Production Server Specifications
- **Operating System**: Ubuntu 22.04 LTS, Ubuntu 24.04 LTS, or Debian 12
- **CPU**: 2+ vCPUs (4+ recommended for large flight networks)
- **RAM**: 4 GB minimum (8 GB recommended when running local MariaDB + Redis)
- **Storage**: 40 GB+ NVMe SSD
- **Network**: Static IPv4 address with ports `80` (HTTP) and `443` (HTTPS) open.

### Required Software Components
- **PHP**: 8.4.x with extensions: `php8.4-fpm`, `php8.4-mysql`, `php8.4-redis`, `php8.4-mbstring`, `php8.4-xml`, `php8.4-bcmath`, `php8.4-gd`, `php8.4-curl`, `php8.4-zip`, `php8.4-intl`, `php8.4-pcntl`
- **Web Server**: Nginx (1.24+)
- **Database**: MariaDB 11.x (or MySQL 8.0+)
- **In-Memory Cache & Queue**: Redis 7.x
- **Node.js**: Node 20.x LTS + NPM
- **Composer**: Composer 2.7+

---

## 2. 🚀 Bare-Metal LEMP Installation

### Step 1: System Packages & PHP 8.4
```bash
# Update repositories and install Ondřej Surý PHP repository
sudo apt update && sudo apt install -y software-properties-common curl git unzip
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Install PHP 8.4 and required extensions
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-redis php8.4-mbstring \
    php8.4-xml php8.4-bcmath php8.4-gd php8.4-curl php8.4-zip php8.4-intl php8.4-pcntl

# Install Nginx, MariaDB, and Redis
sudo apt install -y nginx mariadb-server redis-server
```

### Step 2: Database Setup
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE vops_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vops_user'@'localhost' IDENTIFIED BY 'StrongSecurePasswordHere123!';
GRANT ALL PRIVILEGES ON vops_prod.* TO 'vops_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Application Deployment
```bash
# Clone the repository
sudo mkdir -p /var/www/v-air-ops
sudo chown -R $USER:www-data /var/www/v-air-ops
git clone https://web2.artmex-hosting.com:2223/tathar26/v-ops.git /var/www/v-air-ops

cd /var/www/v-air-ops/v-ops

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and compile frontend assets
npm ci
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate
```

Configure `/var/www/v-air-ops/v-ops/.env` with your production settings:
```ini
APP_NAME="V-Air Ops"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vops.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vops_prod
DB_USERNAME=vops_user
DB_PASSWORD="StrongSecurePasswordHere123!"

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

FORCE_HTTPS=true
```

### Step 4: Run Migrations & Set Permissions
```bash
php artisan migrate --force
php artisan storage:link

# Set strict permissions
sudo chown -R www-data:www-data /var/www/v-air-ops/v-ops/storage /var/www/v-air-ops/v-ops/bootstrap/cache
sudo chmod -R 775 /var/www/v-air-ops/v-ops/storage /var/www/v-air-ops/v-ops/bootstrap/cache
```

---

## 3. 🌐 Nginx Web Server & Reverse Proxy Configuration

Create `/etc/nginx/sites-available/v-air-ops.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name vops.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name vops.yourdomain.com;

    root /var/www/v-air-ops/v-ops/public;
    index index.php;

    # SSL Certificates (managed via Certbot)
    ssl_certificate /etc/letsencrypt/live/vops.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vops.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "no-referrer-when-downgrade";

    charset utf-8;
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site and test configuration:
```bash
sudo ln -s /etc/nginx/sites-available/v-air-ops.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Generate Let's Encrypt SSL
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d vops.yourdomain.com
```

---

## 4. ⚙️ Supervisor Background Queue Workers & Scheduler

Create `/etc/supervisor/conf.d/vops-worker.conf`:

```ini
[program:vops-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/v-air-ops/v-ops/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/v-air-ops/v-ops/storage/logs/worker.log
stopwaitsecs=3600
```

Start the workers:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vops-worker:*
```

### Laravel Scheduler Cron Job
Add the following line to the `www-data` user's crontab:
```bash
sudo crontab -u www-data -e
```
```cron
* * * * * cd /var/www/v-air-ops/v-ops && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. 🚀 Production Caching & Performance Checklist

Execute these commands after each deployment:
```bash
cd /var/www/v-air-ops/v-ops
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
