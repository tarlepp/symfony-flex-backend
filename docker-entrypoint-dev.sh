#!/bin/bash
set -e

#
# If we're starting web-server we need to do following:
#   1) Ensure that /app/var directory exists
#   2) Install all dependencies
#   3) Generate JWT encryption keys + allow apache to read this file
#   4) Clear caches from dev and prod environments
#   5) Create database if it not exists yet
#   6) Run possible migrations, so that database is always up to date
#   7) Install public assets
#   8) Ensure that _all_ files have "correct" permissions
#
# Note that all the chmod stuff is for users who are using docker-compose within Linux environment. More info in link
# below:
#   https://jtreminio.com/blog/running-docker-containers-as-current-host-user/
#

# Step 1
mkdir -p /app/var

# Step 2
composer install --ansi

# Step 3
make generate-jwt-keys
chmod 644 /app/config/jwt/private.pem

# Step 4
php /app/bin/console cache:clear

# Step 5
php /app/bin/console doctrine:database:create --if-not-exists --no-interaction

# Step 6
php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Step 7
php /app/bin/console assets:install --symlink --relative --no-interaction

# Step 8
chmod -R o+s+w /app

exec "$@"
