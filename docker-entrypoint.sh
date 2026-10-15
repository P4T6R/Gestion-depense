#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY doit être fournie dans .env.docker." >&2
    exit 1
fi

mkdir -p \
    /var/www/storage/app/public \
    /var/www/storage/app/private \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache \
    /var/www/backups

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/backups
find /var/www/storage /var/www/bootstrap/cache /var/www/backups -type d -exec chmod 0750 {} +
find /var/www/storage /var/www/bootstrap/cache /var/www/backups -type f -exec chmod 0640 {} +

if [ "${1:-}" = "php" ] || [ "${1:-}" = "artisan" ]; then
    exec gosu www-data "$@"
fi

exec "$@"
