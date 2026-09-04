# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.4

# =======================================================
# Stage 1 — vendor (Composer)
# =======================================================
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-interaction \
        --no-dev \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
        --no-progress

# =======================================================
# Stage 2 — assets (Node / Vite)
# =======================================================
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --prefer-offline --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.js vite.cors.mjs vite.hmr.mjs postcss.config.js tailwind.config.js ./
RUN npm run build

# =======================================================
# Stage 3 — runtime (PHP-FPM + Nginx + Supervisor, Alpine)
# =======================================================
FROM php:${PHP_VERSION}-fpm-alpine AS runtime

ARG APP_UID=1000
ARG APP_GID=1000

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN --mount=type=cache,target=/var/cache/apk,sharing=locked \
    set -eux; \
    apk add --no-cache \
        nginx \
        supervisor \
        curl \
        bash \
        git \
        unzip \
        tzdata; \
    install-php-extensions \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        redis; \
    addgroup -g ${APP_GID} appuser; \
    # Home criado (sem -H) e gravável: dá ao appuser um COMPOSER_HOME válido para
    # o cache, evitando o warning "Cannot create cache directory" no composer install.
    adduser -u ${APP_UID} -G appuser -D appuser; \
    # Diretórios que nginx/php-fpm/supervisord precisam escrever
    mkdir -p /var/lib/nginx/tmp /var/log/nginx /run/nginx /var/log/supervisor; \
    chown -R appuser:appuser \
        /var/lib/nginx \
        /var/log/nginx \
        /run/nginx \
        /var/log/supervisor \
        /usr/local/etc/php-fpm.d

WORKDIR /var/www/html

COPY docker/nginx.conf        /etc/nginx/nginx.conf
COPY docker/php-fpm.conf      /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/supervisord.conf  /etc/supervisord.conf

# Composer disponível em runtime: o entrypoint usa-o em DEV para regenerar o
# vendor/ quando o bind-mount do host sobrepõe o vendor/ compilado na imagem
# (clone novo ou git pull). Também habilita os comandos manuais do README.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY --from=vendor --chown=appuser:appuser /app/vendor ./vendor
COPY --chown=appuser:appuser . .
COPY --from=assets --chown=appuser:appuser /app/public/build ./public/build
COPY --chown=appuser:appuser docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod -R ug+rwX storage bootstrap/cache && \
    chmod +x /usr/local/bin/entrypoint.sh && \
    php artisan package:discover --ansi || true

USER appuser

ENTRYPOINT ["entrypoint.sh"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

EXPOSE 8080
