#!/bin/sh
set -e

# Railway injects env at runtime, so cache config now (NOT at build time, where
# env is absent). route:cache/view:cache are env-independent but kept here too.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hand off to the image's default command (frankenphp run, see Dockerfile CMD).
exec "$@"
