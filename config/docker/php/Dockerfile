# syntax=docker/dockerfile:experimental
FROM webforgelabs/php-nodejs:php7.4-node12

RUN mkdir /app
WORKDIR /app

COPY config/php/*.ini /usr/local/etc/php/conf.d/

RUN composer self-update --2

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
