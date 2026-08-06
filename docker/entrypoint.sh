#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
  echo "Ignite cannot start: APP_KEY is not set." >&2
  echo "Generate one, paste it into your .env, then recreate the containers:" >&2
  echo "  docker compose run --rm --no-deps --entrypoint php web artisan key:generate --show" >&2
  echo "  docker compose up -d --force-recreate" >&2
  exit 1
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hand off to the image's default command (frankenphp run, see Dockerfile CMD).
exec "$@"
