#!/bin/sh
set -e

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
