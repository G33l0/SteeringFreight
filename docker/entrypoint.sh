#!/bin/sh
# Prepares the container on every boot: key, database, caches, first admin.
#
# A platform host gives a container a fresh, empty filesystem each time it
# starts, so this has to be safe to run repeatedly and must never assume the
# work of a previous boot survived.
set -e

# FrankenPHP listens on the port the platform assigns.
export SERVER_NAME=":${PORT:-8080}"

# Laravel refuses to boot without an application key. Set APP_KEY in the host's
# environment for a stable one; without it we make a throwaway key, which is
# fine for a preview but signs everyone out whenever the container restarts.
if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
    echo "portlane: APP_KEY was not set. Using a temporary key for this container only."
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public storage/app/private bootstrap/cache

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/app/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    [ -f "$DB_FILE" ] || : > "$DB_FILE"
fi

php artisan migrate --force

# The content the site needs to work: settings, tracking statuses, services,
# pages and questions. Safe to re-run; it only fills in what is missing.
if [ "${SEED_ON_BOOT:-false}" = "true" ]; then
    php artisan db:seed --force || true
fi

# Sample shipments, customers and reviews, so a preview host that wipes its disk
# on restart still comes back with something to click on. Every record it makes
# is labelled as sample data on the site. Never set this on a real install.
if [ "${DEMO_DATA:-false}" = "true" ]; then
    php artisan db:seed --force --class='Database\Seeders\DemoDataSeeder' || true
fi

# First administrator, from the host's environment. Nothing is created unless
# both variables are set, and it is skipped once the account exists.
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    php artisan portlane:create-admin \
        --name="${ADMIN_NAME:-Administrator}" \
        --email="${ADMIN_EMAIL}" \
        --password="${ADMIN_PASSWORD}" \
        --role=administrator || echo "portlane: administrator already exists, leaving it alone."
fi

php artisan storage:link || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
