FROM dunglas/frankenphp:1.12.4-php8.5 AS runtime

RUN install-php-extensions pdo_pgsql intl zip bcmath opcache


FROM runtime AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


FROM base AS development

# Use ARG to define environment variables passed from the Docker build command or Docker Compose.
ARG XDEBUG_ENABLED=true
ARG XDEBUG_MODE=develop,debug
ARG XDEBUG_HOST=host.docker.internal
ARG XDEBUG_IDE_KEY=DOCKER
ARG XDEBUG_LOG=/dev/stdout
ARG XDEBUG_LOG_LEVEL=0

USER root

RUN if [ "${XDEBUG_ENABLED}" = "true" ]; then \
    install-php-extensions xdebug && \
    echo "xdebug.mode=${XDEBUG_MODE}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=${XDEBUG_IDE_KEY}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log=${XDEBUG_LOG}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log_level=${XDEBUG_LOG_LEVEL}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=${XDEBUG_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
fi

WORKDIR /app

COPY docker/entrypoint.dev.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY docker/Caddyfile /etc/caddy/Caddyfile

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]


FROM base AS builder

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --no-progress

COPY package.json package-lock.json ./

RUN npm ci

COPY . /app

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

ARG VITE_APP_NAME=Ignite
ENV VITE_APP_NAME=$VITE_APP_NAME

ARG VITE_FORMBRICKS_WORKSPACE_ID
ENV VITE_FORMBRICKS_WORKSPACE_ID=$VITE_FORMBRICKS_WORKSPACE_ID

ARG VITE_FORMBRICKS_APP_URL
ENV VITE_FORMBRICKS_APP_URL=$VITE_FORMBRICKS_APP_URL

RUN npm run build && rm -rf node_modules


FROM runtime AS production

WORKDIR /app

COPY --from=builder /app /app

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
