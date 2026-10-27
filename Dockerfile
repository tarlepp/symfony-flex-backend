# syntax=docker/dockerfile:1.19
FROM php:8.5.12-fpm-bookworm@sha256:740a5adab5af19504d8aa0754e903e20b40b0153cfd4013c11e4df2029a7b73c AS base

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
COPY --from=mlocati/php-extension-installer:2.12.3 /usr/bin/install-php-extensions /usr/local/bin/

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
COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/composer

# Enable Composer autocompletion
RUN composer completion bash > /etc/bash_completion.d/composer

WORKDIR /app

EXPOSE 9000

FROM base AS development

# Let's use bash as a default shell with login each time
SHELL ["/bin/bash", "--login", "-c"]

ARG HOST_UID=1000
ARG HOST_GID=1000

ENV PATH="$PATH:/home/dev/.composer/vendor/bin:/app/vendor/bin"
ENV NVM_VERSION=v0.40.8
ENV NODE_VERSION=26.12.1

RUN apt-get update \
    && apt-get install -y \
        bash-completion \
        build-essential \
        gnupg \
        iproute2 \
        locales \
        nano \
        python3 \
        python3-venv \
        sudo \
        vim \
    && apt-get upgrade -y \
    && rm -rf /var/lib/apt/lists/*

RUN echo 'deb http://download.opensuse.org/repositories/shells:/fish/Debian_12/ /' | \
        tee /etc/apt/sources.list.d/shells:fish.list \
    && curl -fsSL https://download.opensuse.org/repositories/shells:fish/Debian_12/Release.key | \
        gpg --dearmor | \
        tee /etc/apt/trusted.gpg.d/shells_fish.gpg > /dev/null \
    && apt-get update \
    && apt-get install -y \
        fish \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen \
    && locale-gen

ENV LANG=en_US.UTF-8
ENV LANGUAGE=en_US:en
ENV LC_ALL=en_US.UTF-8

RUN install-php-extensions xdebug

# These files are also mounted by compose.yaml for local configuration changes.
COPY ./docker/php/php-dev.ini /usr/local/etc/php/php.ini
COPY ./docker/php/www-dev.conf /usr/local/etc/php-fpm.d/www.conf

RUN chmod -R o+s+w /usr/local/etc/php

RUN groupadd --gid ${HOST_GID} dev \
    && useradd \
        -p $(perl -e 'print crypt($ARGV[0], "password")' 'dev') \
        --uid ${HOST_UID} \
        --gid ${HOST_GID} \
        --shell /bin/bash \
        --create-home dev \
    && usermod -a -G www-data,sudo dev \
    && echo 'dev ALL=(ALL) ALL' >> /etc/sudoers

RUN mkdir -p /home/dev/.config/fish/completions \
    && mkdir -p /home/dev/.config/fish/functions \
    && mkdir -p /home/dev/.local/share \
    && mkdir -p /home/dev/.npm \
    && mkdir -p /home/dev/.nvm \
    && chmod 777 -R /home/dev

COPY --chown=dev:dev . .

RUN chmod +x /app/bin/console \
    && chmod +x /app/docker-entrypoint-dev.sh \
    && chmod +x /usr/bin/composer

USER dev

RUN python3 -m venv /home/dev/.venv \
    && python3 -m venv /home/dev/.local/share/thefuck \
    && /home/dev/.local/share/thefuck/bin/python -m pip install --no-cache-dir thefuck \
    && mkdir -p /home/dev/.local/bin \
    && ln -s /home/dev/.local/share/thefuck/bin/thefuck /home/dev/.local/bin/thefuck

ENV VIRTUAL_ENV=/home/dev/.venv
ENV PATH="$VIRTUAL_ENV/bin:$PATH:/home/dev/.local/bin"

RUN echo 'eval "$(thefuck --alias)"' >> /home/dev/.bashrc \
    && echo 'export PATH="/home/dev/.venv/bin:$PATH"' >> /home/dev/.profile

RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_VERSION/install.sh | bash

USER root

RUN chmod 777 -R /home/dev

USER dev

RUN source ~/.nvm/nvm.sh \
    && nvm install $NODE_VERSION \
    && npm install -g composer-version \
    && npm install --prefix /tmp/npm-fixes --no-save --no-package-lock --ignore-scripts --no-audit --no-fund \
        brace-expansion@5.0.11 \
        undici@6.29.1 \
    && cp -a /tmp/npm-fixes/node_modules/brace-expansion/. "$(npm root -g)/npm/node_modules/brace-expansion/" \
    && cp -a /tmp/npm-fixes/node_modules/undici/. "$(npm root -g)/npm/node_modules/undici/" \
    && rm -rf /tmp/npm-fixes

RUN echo 'source /usr/share/bash-completion/bash_completion' >> /home/dev/.bashrc \
    && echo 'alias console="/app/bin/console"' >> /home/dev/.bashrc

COPY ./docker/fish /home/dev/.config/fish/

USER root

RUN chmod 777 -R /home/dev /tmp \
    && rm -rf /tmp/fish.node \
    && chgrp -hR dev /home/dev

USER dev

ENTRYPOINT ["/app/docker-entrypoint-dev.sh"]

# Keep production last so builds without --target produce the production image.
FROM base AS production

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

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

ENTRYPOINT ["/app/docker-entrypoint.sh"]
