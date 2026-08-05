#!/bin/sh
set -e

if [ ! -d "/app/vendor" ]; then
  composer install --no-interaction --prefer-dist --no-progress
fi

if ! grep -q '^APP_KEY=base64:' /app/.env; then
  php artisan key:generate
  unset APP_KEY
fi

php artisan migrate --force && php artisan db:seed --force

php artisan optimize:clear

# Hand off to the image's default command (frankenphp run, see Dockerfile CMD).
exec "$@"
