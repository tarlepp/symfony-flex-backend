#!/bin/bash
set -e

#
# If we're starting web-server we need to do following:
#   0) Basic linting of current JSON configuration file
#   1) Ensure that /app/var directory exists
#   2) Generate JWT encryption keys + allow web server to read this file
#   3) Clear and warmup caches on current environments
#   4) Create database if it not exists yet
#   5) Run possible migrations, so that database is always up to date
#   6) Install public assets
#   7) Copy _all_ files to shared folder so that Nginx use those properly
#
# Note that in production environment we cannot use `symfony` binary to wrap
# these commands because for some reason current environment variables from
# docker context won't be passed to php process.
#

if [[ "$1" = 'php-fpm' ]]; then
    # Step 0
    make lint-configuration

    # Step 1
    mkdir -p /app/var

    # Step 2
    make generate-jwt-keys
    chmod 644 /app/config/jwt/private.pem

    # Step 3
    ./bin/console cache:clear --no-ansi
    ./bin/console cache:warmup --no-ansi

    # Step 4
    ./bin/console doctrine:database:create --if-not-exists --no-interaction --no-ansi

    # Step 5
    ./bin/console doctrine:migrations:migrate --no-interaction --no-ansi --allow-no-migration

    # Step 6
    ./bin/console assets:install --symlink --relative --no-interaction --no-ansi --env prod

    # Step 7
    chmod -R o+s+w /app
    cp -bar /app /shared/app
fi

exec "$@"
