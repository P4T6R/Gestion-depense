# Image PHP 8.2 avec FPM
FROM php:8.2-fpm AS app

# Arguments
ARG user=www
ARG uid=1000

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Node.js (pour le build Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer l'utilisateur système
RUN useradd -G www-data -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Définir le répertoire de travail
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www

# Copier les fichiers du projet
COPY . /var/www

# Les secrets sont injectés au démarrage, jamais copiés dans l’image.

# Installer les dépendances PHP
RUN composer install --optimize-autoloader --no-dev

# Build des assets frontend (génère public/build/manifest.json)
RUN npm ci && npm run build && rm -rf node_modules

# Permissions
RUN chown -R $user:www-data /var/www
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copier le script d'entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000

# Utiliser l'entrypoint personnalisé
ENTRYPOINT ["docker-entrypoint.sh"]

# Nginx et PHP livrent les assets issus du même build ; aucun volume ne masque le code.
FROM nginx:alpine AS web
COPY --from=app /var/www/public /var/www/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
