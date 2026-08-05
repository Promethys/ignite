#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hand off to the image's default command (frankenphp run, see Dockerfile CMD).
exec "$@"
