#!/bin/bash

# Exit on error
set -e

echo "Starting Laravel application..."

# Wait for database to be ready
if [ ! -z "$DB_HOST" ]; then
    echo "Waiting for database..."
    for i in {1..30}; do
        if nc -z "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; then
            echo "Database is ready!"
            break
        fi
        echo "Database not ready, waiting... ($i/30)"
        sleep 1
    done
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache config, routes, and views
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM and Nginx
echo "Starting PHP-FPM and Nginx..."

# Start PHP-FPM in background
php-fpm &

# Start Nginx in foreground
exec nginx -g "daemon off;"
