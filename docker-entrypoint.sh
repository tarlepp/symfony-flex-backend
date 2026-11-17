#!/bin/bash

set -e

# Note that in production environment we cannot use `symfony` binary to wrap
# these commands because for some reason current environment variables from
# docker context won't be passed to php process.

if [[ "$1" = 'php-fpm' ]]; then
    # Validate application configuration before production setup.
    make lint-configuration

    # Prepare runtime storage and authentication keys.
    mkdir -p /app/var

    make generate-jwt-keys
    chmod 644 /app/config/jwt/private.pem

    # Create the database and apply pending migrations.
    ./bin/console doctrine:database:create --if-not-exists --no-interaction --no-ansi

    ./bin/console doctrine:migrations:migrate --no-interaction --no-ansi --allow-no-migration

    # Install public assets.
    ./bin/console assets:install --symlink --relative --no-interaction --no-ansi --env prod

    # Set runtime ownership and synchronize application contents for Nginx.
    chown -R www-data:www-data /app/var

    mkdir -p /shared/app
    find /shared/app -mindepth 1 -delete
    cp -a /app/. /shared/app/
fi

exec "$@"
