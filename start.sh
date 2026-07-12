#!/bin/sh

set -e  # Stop on error

echo "=== Starting deployment script ==="

echo "Clearing config cache..."
php artisan config:clear

echo "Clearing view cache..."
php artisan view:clear

echo "Running migrations..."
php artisan migrate --force --verbose

echo "Migrations completed."

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;'