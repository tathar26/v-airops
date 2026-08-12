#!/bin/sh
set -e

# Ensure storage directories exist and have correct permissions
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
chown -R www-data:www-data /var/www/html/storage

# Wait for database if needed, then run migrations
echo "Running database migrations..."
php artisan migrate --force || echo "WARNING: Migrations failed (tables may already exist). Continuing boot process..."

if [ "$APP_ENV" = "staging" ]; then
    echo "Staging environment detected. Seeding database with example data..."
    php artisan db:seed --force || echo "WARNING: Seeding failed (data may already exist)."
fi

echo "Discovering packages..."
php artisan package:discover --ansi

echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
