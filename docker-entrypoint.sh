#!/bin/bash

#
# Initialize the production PHP runtime before php-fpm starts.
#
# Usage:
#   Invoked as the production image entrypoint with the container command.
#
# Details:
#   The php-fpm command validates configuration, prepares storage, database and
#   assets, then refreshes the shared Nginx application copy. Other commands
#   bypass initialization and are executed directly.
#
# Callers:
#   Docker:
#     - `Dockerfile` production image `ENTRYPOINT`.
#

set -e

# Run Symfony Console directly: the Symfony CLI wrapper does not pass Docker
# context environment variables through to the PHP process in production.

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
    # Replace the shared Nginx copy with the initialized application tree.
    find /shared/app -mindepth 1 -delete
    cp -a /app/. /shared/app/
fi

exec "$@"
