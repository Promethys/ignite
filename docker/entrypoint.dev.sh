#!/bin/sh
set -e

if [ ! -f "/app/vendor/autoload.php" ]; then
  composer install --no-interaction --prefer-dist --no-progress
fi

if ! grep -q '^APP_KEY=base64:' /app/.env; then
  php artisan key:generate
  unset APP_KEY
fi

if ! grep -q '^USER_DATA_MASTER_KEY=base64:' /app/.env; then
  master_key=$(php artisan key:generate --show)
  if grep -q '^USER_DATA_MASTER_KEY=' /app/.env; then
    sed -i "s|^USER_DATA_MASTER_KEY=.*|USER_DATA_MASTER_KEY=$master_key|" /app/.env
  else
    echo "USER_DATA_MASTER_KEY=$master_key" >> /app/.env
  fi
  unset USER_DATA_MASTER_KEY
fi

php artisan migrate --force && php artisan db:seed --force

# Hand off to the image's default command (frankenphp run, see Dockerfile CMD).
exec "$@"
