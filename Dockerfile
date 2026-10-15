# syntax=docker/dockerfile:1.7

FROM composer:2.8 AS composer-bin

FROM php:8.4-fpm-bookworm AS php-base

ENV APP_HOME=/var/www \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        default-mysql-client \
        gosu \
        libfcgi-bin \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        xml \
        xmlwriter \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini

WORKDIR ${APP_HOME}

FROM php-base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM node:22-bookworm-slim AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php-base AS app

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chmod 0755 /usr/local/bin/docker-entrypoint \
    && mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /var/www/backups \
    && chown -R www-data:www-data storage bootstrap/cache /var/www/backups \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
    && php artisan package:discover --ansi

EXPOSE 9000
ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm", "-F"]

FROM nginx:1.28-alpine AS web

COPY --from=app /var/www/public /var/www/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=5 \
    CMD wget --quiet --spider http://127.0.0.1/login || exit 1
CMD ["nginx", "-g", "daemon off;"]
