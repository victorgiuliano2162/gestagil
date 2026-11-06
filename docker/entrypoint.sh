#!/bin/sh
set -e

# Ensure storage directories exist
mkdir -p /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/private/logos \
         /backups

# Ensure sqlite database file exists if sqlite connection is used
if [ "$DB_CONNECTION" = "sqlite" ] && [ -n "$DB_DATABASE" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    if [ ! -f "$DB_DATABASE" ]; then
        touch "$DB_DATABASE"
    fi
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan optimize || true
fi

exec "$@"
