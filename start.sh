#!/bin/bash

# Exit on error
set -e

echo "Starting Laravel application..."

# Wait for database to be ready
if [ ! -z "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    for i in {1..60}; do
        if timeout 1 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/${DB_PORT:-3306}" 2>/dev/null; then
            echo "Database is ready!"
            break
        fi
        echo "Database not ready, waiting... ($i/60)"
        sleep 1
    done
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force 2>&1 || true

# Cache config and routes
echo "Caching configuration..."
php artisan config:cache 2>&1 || true
php artisan route:cache 2>&1 || true
php artisan view:cache 2>&1 || true

# Create storage link if needed
if [ ! -L /app/public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link 2>&1 || true
fi

echo "Starting services..."

# Start PHP-FPM in background
php-fpm &
PHP_FPM_PID=$!

# Start Nginx in foreground
nginx -g "daemon off;" &
NGINX_PID=$!

# Handle signals
trap "kill $PHP_FPM_PID $NGINX_PID" SIGTERM SIGINT

# Wait for services
wait
