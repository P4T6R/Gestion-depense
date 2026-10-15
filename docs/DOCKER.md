# Exécution complète avec Docker

La pile contient six services : MySQL, migration, PHP-FPM/Laravel, Nginx avec les assets Vite, worker de file d’attente et scheduler. phpMyAdmin reste facultatif avec le profil `tools`.

## Première installation

```bash
cp .env.docker.example .env.docker
php artisan key:generate --env=docker
docker compose config --quiet
docker compose build
docker compose up -d
docker compose ps
```

Sous Windows PowerShell, utilisez `Copy-Item .env.docker.example .env.docker` à la place de `cp`.

L’application est disponible sur `http://localhost:8080`. Les données MySQL, les fichiers privés et les sauvegardes sont conservés dans des volumes Docker nommés.

Ne lancez jamais `docker compose down -v` sur une installation contenant des données à conserver : l’option `-v` supprime les volumes.

## Gmail SMTP

Le compte Google doit avoir la validation en deux étapes. Créez ensuite un mot de passe d’application Google à 16 caractères et placez-le uniquement dans le fichier ignoré `.env.docker` :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=crowpeter@gmail.com
MAIL_PASSWORD=mot_de_passe_application_google
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=crowpeter@gmail.com
```

Le mot de passe normal du compte Gmail ne doit pas être utilisé. Après modification :

```bash
docker compose up -d --force-recreate app queue scheduler
docker compose exec app php artisan config:clear
docker compose exec app php artisan mail:test-reviewers
docker compose exec app php artisan mail:test-reviewers --send
docker compose exec queue php artisan queue:restart
```

Le test réel exige aussi que les utilisateurs administrateur, directeur et comptable possèdent leurs véritables adresses dans la base.

## Exploitation

```bash
docker compose logs --tail=100 app web queue scheduler
docker compose exec app php artisan migrate:status
docker compose exec app php artisan queue:failed
docker compose exec app php artisan schedule:list
docker compose exec app php artisan backup:application
```

Pour activer phpMyAdmin localement :

```bash
docker compose --profile tools up -d phpmyadmin
```

## Production derrière un reverse proxy TLS

Dans `.env.docker`, définir au minimum :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://depenses.example.com
APP_FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=adresse_du_reverse_proxy
```

Utiliser des mots de passe MySQL aléatoires, une clé `APP_KEY` stable et une destination externe chiffrée pour les sauvegardes.
