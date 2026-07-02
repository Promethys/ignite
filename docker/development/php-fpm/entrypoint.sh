#!/bin/sh
set -e

# Clear configurations to avoid caching issues in development
echo "Clearing configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force

# Run the default command (e.g., php-fpm or bash)
exec "$@"
