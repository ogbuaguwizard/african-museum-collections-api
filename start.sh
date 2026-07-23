#!/bin/sh

set -e

echo "Installing Node dependencies..."
npm install

echo "Building Vite assets..."
npm run build

echo "Caching configuration..."
php artisan config:clear
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

# Start import in background (writes logs)
echo "Starting import in background..."
nohup php artisan import:met --limit=1000 --offset=0 >> storage/logs/import.log 2>&1 &

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;'