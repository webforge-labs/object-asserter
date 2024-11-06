FROM webforgelabs/php-nodejs:php7.4-node12

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN mkdir /app
WORKDIR /app

COPY config/php/*.ini /usr/local/etc/php/conf.d/

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
