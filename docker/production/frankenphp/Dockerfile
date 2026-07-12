# syntax=docker/dockerfile:1

# ---- Stage 1: build dependencies + front-end assets ----
# Base is the FrankenPHP image (has PHP) because `npm run build` runs
# `php artisan` via the Wayfinder + i18n Vite plugins.
FROM dunglas/frankenphp:1.12.4-php8.5 AS builder

# Node.js 22 (for Vite) + unzip/git (for composer) on top of the PHP base.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions required to run artisan during the build.
RUN install-php-extensions pdo_pgsql intl zip bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy the full source first: composer's post-autoload scripts (package:discover)
# need artisan + the app present.
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress

ARG VITE_APP_NAME=Ignite
ENV VITE_APP_NAME=$VITE_APP_NAME

# Build the client bundle (emits public/build). Drop node_modules afterward.
RUN npm ci && npm run build && rm -rf node_modules

# ---- Stage 2: runtime ----
FROM dunglas/frankenphp:1.12.4-php8.5 AS production

RUN install-php-extensions pdo_pgsql intl zip bcmath opcache

WORKDIR /app

# Bring over the fully-built app (vendor + public/build included).
COPY --from=builder /app /app

# Our Railway-tuned Caddyfile and startup script.
COPY docker/production/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY docker/production/frankenphp/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
# Binds a high port ($PORT), so no setcap/privileged binding is needed.
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
