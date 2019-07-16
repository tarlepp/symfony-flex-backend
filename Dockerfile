FROM composer:1.8.6 AS composer
FROM php:7.2.19-fpm

RUN apt-get update && apt-get install -y \
    zlib1g-dev libxml2-dev nano vim git unzip jq \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) bcmath \
    && docker-php-ext-install pdo \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip

# copy the Composer PHAR from the Composer image into the PHP image
COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV APP_ENV prod

WORKDIR /app

COPY . /app
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

RUN chmod +x /app/bin/console
RUN chmod +x /app/docker-entrypoint.sh
RUN chmod +x /usr/bin/composer

RUN rm -rf /app/var \
    && mkdir -p /app/var \
    && php -d memory_limit=-1 /usr/bin/composer install --no-dev --optimize-autoloader

EXPOSE 9000

ENTRYPOINT ["/app/docker-entrypoint.sh"]
