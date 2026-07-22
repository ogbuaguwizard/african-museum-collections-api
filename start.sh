#!/bin/sh

set -e

echo "Installing Node dependencies..."
npm install

echo "Building Vite assets..."
npm run build

echo "Caching configuration..."
php artisan config:cache

# Force HTTPS for asset URLs
php artisan config:set app.url=https://african-artifact-collections.onrender.com

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;'