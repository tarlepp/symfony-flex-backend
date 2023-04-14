#!/bin/bash
set -e

#
# If we're starting web-server we need to do following:
#   0) Basic linting of current JSON configuration file
#   1) Determine docker container IP address, so that we can use it within
#      XDebug configuration. Copy php.ini file to correct location and modify
#      it to contain correct remote host value. With this we don't need to
#      build container each time we want to change something in php.ini file.
#      Also we want to export host IP so that we can use that within
#      `check.php` to check that current environment is compatible with Symfony
#   2) Install all dependencies
#   3) Generate JWT encryption keys
#   4) Create database if it not exists yet
#   5) Run possible migrations, so that database is always up to date
#   6) Add needed symfony console autocomplete for bash
#

# Step 0
make lint-configuration

# Step 1
if [[ -z "${DOCKER_WITH_MAC}" ]]; then
    # Not Mac, so determine actual docker container IP address
    HOST=$(/sbin/ip route|awk '/default/ { print $3 }')
else
    # Otherwise use special value, which works with Mac
    HOST="docker.for.mac.localhost"
fi

cp /app/docker/php/php-dev.ini /usr/local/etc/php/php.ini
sed -i "s/xdebug\.client_host \=.*/xdebug\.client_host\ = $HOST/g" /usr/local/etc/php/php.ini

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
