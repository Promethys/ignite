#!/bin/sh
set -e

if [ ! -d "/app/vendor" ]; then
  composer install
fi

php artisan optimize:clear

php artisan migrate --force && php artisan db:seed --force

# Hand off to the image's default command (frankenphp run, see Dockerfile CMD).
exec "$@"
