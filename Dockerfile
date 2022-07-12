FROM php:8.1.6-fpm

ENV APP_ENV prod
ENV APP_DEBUG 0
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get update \
    && apt-get install -y \
        zlib1g-dev libzip-dev libxml2-dev libicu-dev g++ git unzip jq wget \
    && rm -rf /var/lib/apt/lists/*

# Copy the install-php-extensions (Easily install PHP extension in official PHP Docker containers)
COPY --from=mlocati/php-extension-installer:1.5.16 /usr/bin/install-php-extensions /usr/local/bin/

# Install and enable all necessary PHP extensions
RUN install-php-extensions \
    apcu \
    bcmath \
    igbinary \
    intl \
    opcache \
    pdo_mysql \
    zip

# Copy the Composer PHAR from the Composer image into the PHP image
COPY --from=composer:2.3.9 /usr/bin/composer /usr/bin/composer

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
    && rm -rf /app/public/check.php \
    && php -d memory_limit=-1 /usr/bin/composer install --no-dev --optimize-autoloader

EXPOSE 9000

ENTRYPOINT ["/app/docker-entrypoint.sh"]
