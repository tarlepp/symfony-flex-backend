# syntax=docker/dockerfile:1.7-labs
FROM php:8.4.13-fpm-bookworm

ENV APP_ENV prod
ENV APP_DEBUG 0
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get update \
    && apt-get install -y \
        g++ \
        git \
        jq \
        libicu-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        wget \
        zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Copy the install-php-extensions (Easily install PHP extension in official PHP Docker containers)
COPY --from=mlocati/php-extension-installer:2.8.5 /usr/bin/install-php-extensions /usr/local/bin/

# Install and enable all necessary PHP extensions
RUN install-php-extensions \
    apcu \
    bcmath \
    igbinary \
    intl \
    opcache \
    pdo_mysql \
    zip

# Install security updates
RUN apt-get update \
    && apt-get install -y \
        debsecan \
    && apt-get install --no-install-recommends -y \
        $(debsecan --suite bookworm --format packages --only-fixed) \
    && rm -rf /var/lib/apt/lists/*

# Copy the Composer PHAR from the Composer image into the PHP image
COPY --from=composer:2.8.10 /usr/bin/composer /usr/bin/composer

# Enable Composer autocompletion
RUN composer completion bash > /etc/bash_completion.d/composer

WORKDIR /app

COPY --exclude=./docker/* . /app
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN chmod +x /app/bin/console
RUN chmod +x /app/docker-entrypoint.sh
RUN chmod +x /usr/bin/composer

RUN rm -rf /app/var \
    && mkdir -p /app/var \
    && rm -rf /app/public/check.php \
    && php -d memory_limit=-1 /usr/bin/composer install --no-dev --optimize-autoloader \
    && php /usr/bin/composer audit --abandoned=ignore

EXPOSE 9000

ENTRYPOINT ["/app/docker-entrypoint.sh"]
