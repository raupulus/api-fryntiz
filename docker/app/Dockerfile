FROM php:8.4-fpm-alpine AS base

LABEL maintainer="Raul Caro Pastorino <raul@raupulus.dev>"
LABEL description="Api Raupulus - Aplicacion Laravel 13"

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost \
    STORAGE_PATH=/var/www/storage

RUN apk add --no-cache \
    curl git zip unzip libpq-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    icu-dev libxml2-dev oniguruma-dev supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd intl mbstring xml opcache pcntl bcmath \
    && rm -rf /var/cache/apk/*

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY docker/app/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . /var/www

RUN composer install --optimize-autoloader --no-dev --no-interaction --no-progress

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

COPY docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
