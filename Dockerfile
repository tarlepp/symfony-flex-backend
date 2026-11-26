# syntax=docker/dockerfile:1.19
ARG NVM_VERSION=v0.40.8
ARG NVM_INSTALLER_SHA256=48a0eee9a60e07422dce0eb5774754c83889570ca1ee2566c516acbe8af03a9e
ARG NODE_VERSION=26.12.1

FROM php:8.5.13-fpm-trixie@sha256:eaa1aba4e2842325be88dadda73afebf0dfb3f594fcfcc80c1a93335fa88d4e6 AS php-upstream
FROM mlocati/php-extension-installer:2.13.4@sha256:584fcc217b94a10187367552f6571cd8680ee4bf22bd752f1087a7d55e35e7de AS extension-installer-upstream
FROM composer:2.11.2@sha256:cd3a90dc5624c59a2f9ddf80dad08569f06416db31de519024f249d63bca554b AS composer-upstream

FROM php-upstream AS maintenance

COPY --from=composer-upstream /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install --no-install-recommends -y jq make \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

FROM php-upstream AS base

RUN apt-get update \
    && apt-get install --only-upgrade -y apt \
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
COPY --from=extension-installer-upstream /usr/bin/install-php-extensions /usr/local/bin/

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
        $(debsecan --suite trixie --format packages --only-fixed) \
    && rm -rf /var/lib/apt/lists/*

# Copy the Composer PHAR from the Composer image into the PHP image
COPY --from=composer-upstream /usr/bin/composer /usr/bin/composer

# Enable Composer autocompletion
RUN composer completion bash > /etc/bash_completion.d/composer

WORKDIR /app

EXPOSE 9000

FROM base AS development

# Let's use bash as a default shell with login each time
SHELL ["/bin/bash", "--login", "-c"]

ARG HOST_UID=1000
ARG HOST_GID=1000
ARG NVM_VERSION
ARG NVM_INSTALLER_SHA256
ARG NODE_VERSION

ENV PATH="$PATH:/home/dev/.composer/vendor/bin:/app/vendor/bin"
ENV NVM_VERSION=${NVM_VERSION}
ENV NODE_VERSION=${NODE_VERSION}

# Debian slim otherwise excludes fzf integration and Fish's HTML help.
RUN printf '%s\n' \
        'path-include /usr/share/doc/fzf/examples/completion.bash' \
        'path-include /usr/share/doc/fzf/examples/key-bindings.bash' \
        'path-include /usr/share/doc/fish/*' \
        > /etc/dpkg/dpkg.cfg.d/zz-fzf \
    && apt-get update \
    && apt-get install -y \
        bash-completion \
        build-essential \
        fzf \
        gnupg \
        iproute2 \
        locales \
        nano \
        python3 \
        python3-venv \
        sudo \
        vim \
        w3m \
    && apt-get upgrade -y \
    && rm -rf /var/lib/apt/lists/*

ARG BLESH_VERSION=0.4.0-devel3
ARG BLESH_ARCHIVE_REVISION=2
ARG BLESH_SHA256=bdcdcfff216495403adf82a701fe41675f64b64644dc77caabd3e015871ebd61

RUN curl --fail --silent --show-error --location \
        --output /tmp/blesh.tar.xz \
        "https://github.com/akinomyoga/ble.sh/releases/download/v${BLESH_VERSION}/ble-${BLESH_VERSION}-${BLESH_ARCHIVE_REVISION}.tar.xz" \
    && printf '%s  /tmp/blesh.tar.xz\n' "$BLESH_SHA256" | sha256sum --check --strict \
    && mkdir -p /usr/local/share/blesh \
    && tar -xJf /tmp/blesh.tar.xz --strip-components=1 -C /usr/local/share/blesh \
    && rm /tmp/blesh.tar.xz

RUN echo 'deb http://download.opensuse.org/repositories/shells:/fish/Debian_13/ /' | \
        tee /etc/apt/sources.list.d/shells:fish.list \
    && curl -fsSL https://download.opensuse.org/repositories/shells:fish/Debian_13/Release.key | \
        gpg --dearmor | \
        tee /etc/apt/trusted.gpg.d/shells_fish.gpg > /dev/null \
    && apt-get update \
    && apt-get install -y \
        fish \
    && test -s /usr/share/doc/fish/index.html \
    && test -s /usr/share/doc/fish/cmds/string.html \
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

USER dev

COPY --chown=dev:dev requirements.txt /home/dev/requirements.txt

RUN python3 -m venv /home/dev/.venv \
    && /home/dev/.venv/bin/python -m pip install --no-cache-dir -r /home/dev/requirements.txt

# thefuck 3.32 still imports imp, which Python 3.13 removed.
RUN /home/dev/.venv/bin/python - <<'PY'
import pathlib
import site

package = next(
    (pathlib.Path(directory) / "thefuck" for directory in site.getsitepackages()
     if (pathlib.Path(directory) / "thefuck" / "conf.py").is_file()),
    None,
)
if package is None:
    raise SystemExit("thefuck package was not installed")

conf = package / "conf.py"
source = conf.read_text()
legacy_import = "from imp import load_source\n"
loader = """from importlib.util import module_from_spec, spec_from_file_location


def load_source(name, pathname, _file=None):
    module_spec = spec_from_file_location(name, pathname)
    if module_spec is None or module_spec.loader is None:
        raise ImportError(f"Cannot load {pathname}")
    module = module_from_spec(module_spec)
    module_spec.loader.exec_module(module)
    return module
"""
if source.count(legacy_import) != 1:
    raise SystemExit("Unexpected thefuck/conf.py; compatibility patch not applied")
conf.write_text(source.replace(legacy_import, loader, 1))

types = package / "types.py"
source = types.read_text()
if source.count(legacy_import) != 1:
    raise SystemExit("Unexpected thefuck/types.py; compatibility patch not applied")
types.write_text(source.replace(legacy_import, "from .conf import load_source\n", 1))
PY
RUN /home/dev/.venv/bin/python - <<'PY'
import importlib
import pathlib
import tempfile

with tempfile.TemporaryDirectory() as directory:
    module_path = pathlib.Path(directory) / "thefuck_compatibility_test.py"
    module_path.write_text("answer = 42\n")
    module = importlib.import_module("thefuck.conf").load_source(
        "thefuck_compatibility_test", str(module_path),
    )
    if module.answer != 42:
        raise SystemExit("thefuck compatibility loader returned an unexpected module")
PY
RUN /home/dev/.venv/bin/thefuck --alias bash > /dev/null \
    && /home/dev/.venv/bin/thefuck --alias fish > /dev/null

ENV VIRTUAL_ENV=/home/dev/.venv
ENV PATH="$VIRTUAL_ENV/bin:$PATH:/home/dev/.local/bin"

RUN echo 'eval "$(thefuck --alias)"' >> /home/dev/.bashrc \
    && echo 'export PATH="/home/dev/.venv/bin:$PATH"' >> /home/dev/.profile

RUN curl --fail --silent --show-error --location \
        --output /tmp/nvm-install.sh \
        "https://raw.githubusercontent.com/nvm-sh/nvm/${NVM_VERSION}/install.sh" \
    && printf '%s  /tmp/nvm-install.sh\n' "$NVM_INSTALLER_SHA256" | sha256sum --check --strict \
    && bash /tmp/nvm-install.sh \
    && rm /tmp/nvm-install.sh

USER root

RUN chmod 777 -R /home/dev

USER dev

RUN source ~/.nvm/nvm.sh \
    && nvm install "$NODE_VERSION" \
    && npm install -g composer-version \
    && npm install --prefix /tmp/npm-fixes --no-save --no-package-lock --ignore-scripts --no-audit --no-fund \
        brace-expansion@5.0.11 \
        undici@6.29.1 \
    && cp -a /tmp/npm-fixes/node_modules/brace-expansion/. "$(npm root -g)/npm/node_modules/brace-expansion/" \
    && cp -a /tmp/npm-fixes/node_modules/undici/. "$(npm root -g)/npm/node_modules/undici/" \
    && rm -rf /tmp/npm-fixes

USER root

COPY --chown=dev:dev . .

RUN chmod +x /app/bin/console \
    && chmod +x /app/docker-entrypoint-dev.sh \
    && chmod +x /usr/bin/composer

USER dev

RUN echo 'source /usr/share/bash-completion/bash_completion' >> /home/dev/.bashrc \
    && echo 'source /usr/share/doc/fzf/examples/completion.bash' >> /home/dev/.bashrc \
    && echo 'source /usr/share/doc/fzf/examples/key-bindings.bash' >> /home/dev/.bashrc \
    && echo 'alias console="/app/bin/console"' >> /home/dev/.bashrc \
    && echo 'source /app/docker/bash/make.sh' >> /home/dev/.bashrc

COPY ./docker/fish /home/dev/.config/fish/

USER root

# Keep the targeted fix after source copies so task rebuilds refresh cached tools.
RUN apt-get update \
    && apt-get install --only-upgrade --no-install-recommends -y \
        libgssapi-krb5-2 \
        libzstd1 \
        linux-libc-dev \
    && rm -rf /var/lib/apt/lists/*

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

# Update only CVE-affected packages, including when the shared base is cached.
RUN apt-get update \
    && apt-get install --only-upgrade --no-install-recommends -y \
        libgssapi-krb5-2 \
        libzstd1 \
        linux-libc-dev \
    && rm -rf /var/lib/apt/lists/*

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
