# Mise en production sécurisée

## Variables obligatoires

Copier `.env.production.example` vers un fichier `.env` conservé uniquement sur le serveur. Générer `APP_KEY`, utiliser une URL HTTPS, conserver `APP_DEBUG=false`, `APP_FORCE_HTTPS=true` et `SESSION_SECURE_COOKIE=true`. Ne jamais commiter les identifiants MySQL ou SMTP.

Le reverse proxy doit transmettre correctement le protocole HTTPS. Vérifier `TrustProxies` selon l’hébergeur avant d’activer la redirection forcée.

## Déploiement

```bash
composer install --no-dev --classmap-authoritative
php artisan migrate --force
php artisan optimize
```

Le worker mail est indispensable :

```bash
php artisan queue:work database --queue=mail,default --sleep=2 --tries=3 --timeout=60 --max-time=3600
```

Le gérer avec Supervisor ou systemd et le relancer à chaque déploiement (`php artisan queue:restart`). Surveiller `php artisan queue:failed`.

## Test SMTP contrôlé

La commande suivante montre les destinataires actifs en masquant leurs adresses :

```bash
php artisan mail:test-reviewers
```

Après validation des vraies adresses et de `MAIL_MAILER=smtp`, envoyer le test puis démarrer le worker :

```bash
php artisan mail:test-reviewers --send
php artisan queue:work database --queue=mail --stop-when-empty
```

## Sauvegardes quotidiennes

Le scheduler lance chaque jour à 02:00 une archive contenant le dump MySQL et `storage/app/private`. Configurer cron :

```cron
* * * * * cd /var/www/gestion-depense && php artisan schedule:run >> /dev/null 2>&1
```

Les archives doivent être placées hors du serveur applicatif ou synchronisées vers un stockage chiffré. Tester régulièrement la restauration. La commande de contrôle est `php artisan backup:application --dry-run`; la commande réelle est `php artisan backup:application`.

## Contrôles après déploiement

- HTTPS et en-tête HSTS présents ; cookie de session `Secure`, `HttpOnly` et `SameSite=Lax`.
- `APP_DEBUG=false` confirmé sur une route inexistante.
- worker mail actif, aucune tâche échouée.
- sauvegarde créée et restauration testée sur une base isolée.
- accès au journal `/audit` limité aux administrateurs.
