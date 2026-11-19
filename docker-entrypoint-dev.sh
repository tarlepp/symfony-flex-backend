#!/bin/bash

# Development PHP container entrypoint.
# Validates configuration, prepares development dependencies and the database,
# then replaces this process with the container command.
# Runs in the application directory; a failed setup step prevents the command
# from starting.

set -e

# Validate application configuration before development setup.
make lint-configuration

# Export host routing and IDE debugging settings.
DOCKER_IP=$(
    /sbin/ip route |
        awk '/default/ { print $3 }'
)

export DOCKER_IP
export XDEBUG_SESSION=PHPSTORM

# Install and audit development dependencies.
COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader

composer audit --abandoned=report

# Prepare authentication keys.
make generate-jwt-keys

# Create the database and apply pending migrations.
./bin/console doctrine:database:create --no-interaction --if-not-exists

./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --all-or-nothing

# Enable Symfony console completion for development shells.
# Append to the development user's shell configuration before command handoff.
./bin/console completion bash >> /home/dev/.bashrc

exec "$@"
