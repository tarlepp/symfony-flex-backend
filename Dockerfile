FROM php:8.0.12-fpm

RUN apt-get update && apt-get install -y \
    zlib1g-dev libzip-dev libxml2-dev libicu-dev g++ git unzip jq wget \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) bcmath \
    && docker-php-ext-install pdo \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install opcache \
    && docker-php-ext-install zip

RUN curl -L -o /usr/local/bin/pickle https://github.com/FriendsOfPHP/pickle/releases/download/v0.7.7/pickle.phar \
    && chmod +x /usr/local/bin/pickle

# Install APCu and APC backward compatibility
RUN pickle install apcu \
    && docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini

# Copy the Composer PHAR from the Composer image into the PHP image
COPY --from=composer:2.1.9 /usr/bin/composer /usr/bin/composer

ENV APP_ENV prod
ENV APP_DEBUG 0
ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /app

COPY . /app
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

RUN chmod +x /app/bin/console
RUN chmod +x /app/docker-entrypoint.sh
RUN chmod +x /usr/bin/composer

RUN curl -s https://api.github.com/repos/fabpot/local-php-security-checker/releases/latest | \
    grep -E "browser_download_url(.+)linux_amd64" | \
    cut -d : -f 2,3 | \
    tr -d \" | \
    xargs -I{} wget -O local-php-security-checker {} \
    && mv local-php-security-checker /usr/bin/local-php-security-checker \
    && chmod +x /usr/bin/local-php-security-checker

RUN rm -rf /app/var \
    && mkdir -p /app/var \
    && php -d memory_limit=-1 /usr/bin/composer install --no-dev --optimize-autoloader

EXPOSE 9000

ENTRYPOINT ["/app/docker-entrypoint.sh"]
