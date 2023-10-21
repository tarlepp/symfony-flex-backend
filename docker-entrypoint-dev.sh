#!/bin/bash
set -e

#
# If we're starting web-server we need to do following:
#   0) Basic linting of current JSON configuration file
#   1) Export needed environment variables
#   2) Install all dependencies
#   3) Generate JWT encryption keys
#   4) Create database if it not exists yet
#   5) Run possible migrations, so that database is always up to date
#   6) Add needed symfony console autocomplete for bash
#

# Step 0
make lint-configuration

# Step 1
DOCKER_IP=$(/sbin/ip route|awk '/default/ { print $3 }')

export DOCKER_IP
export XDEBUG_SESSION=PHPSTORM

# Step 2
COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader

# Step 3
make generate-jwt-keys

# Step 4
./bin/console doctrine:database:create --no-interaction --if-not-exists

# Step 5
./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --all-or-nothing

# Step 6
./bin/console completion bash >> /home/dev/.bashrc

exec "$@"
