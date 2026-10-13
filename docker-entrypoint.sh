#!/bin/sh
set -e

# Une clé stable doit être fournie au runtime, pas générée à chaque conteneur.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -f /var/www/.env ] || ! grep -q '^APP_KEY=base64:' /var/www/.env; then
        echo "APP_KEY doit être fournie dans l'environnement du conteneur." >&2
        exit 1
    fi
fi

mkdir -p /var/www/storage/app/public /var/www/storage/app/private \
    /var/www/storage/framework/cache/data /var/www/storage/framework/sessions \
    /var/www/storage/framework/views /var/www/storage/logs /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
find /var/www/storage/framework /var/www/storage/logs /var/www/bootstrap/cache -type d -exec chmod 775 {} +
find /var/www/storage/framework /var/www/storage/logs /var/www/bootstrap/cache -type f -exec chmod 664 {} +
chmod 755 /var/www/storage /var/www/storage/app /var/www/storage/app/public
find /var/www/storage/app/private -type d -exec chmod 700 {} +
find /var/www/storage/app/private -type f -exec chmod 600 {} +

exec php-fpm
