#!/bin/sh
set -e

# Générer la clé Laravel si elle n'existe pas
if ! grep -q "APP_KEY=base64:" /var/www/.env; then
    echo "Generating Laravel application key..."
    php artisan key:generate --force
fi

# Définir les permissions
chown -R www:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Lancer PHP-FPM
exec php-fpm
