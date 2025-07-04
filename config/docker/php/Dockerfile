FROM php:8.4-fpm AS php-installed

RUN apt-get update && apt-get install -y \
    git zip unzip zlib1g-dev libzip-dev

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY config/docker/php/conf.d/*.ini /usr/local/etc/php/conf.d/

RUN docker-php-ext-install zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN mkdir -p /app/var/cache
WORKDIR /app

FROM php-installed AS ci
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-progress
COPY . .

FROM php-installed AS dev
RUN pecl install xdebug && docker-php-ext-enable xdebug


## install and cache composer dependencies
#COPY composer.json composer.lock ./
#RUN --mount=type=ssh (mkdir -p -m 0600 ~/.ssh && ssh-keyscan -t rsa gitlab.com >> ~/.ssh/known_hosts) \
#   && composer install --prefer-dist --no-interaction --no-scripts
#
#COPY . ./
#
## build
#RUN composer install -o --prefer-dist --no-interaction \
# && chmod +x bin/*
