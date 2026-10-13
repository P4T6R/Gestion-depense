# Gestion des dépenses

Application Laravel pour suivre les chantiers, factures multi-articles, justificatifs et budgets. Le chef de chantier saisit ses dépenses ; le directeur approuve ou rejette les factures en attente ; le comptable valide les factures approuvées. Une facture créée par un directeur passe directement à la comptable : le directeur ne peut ni l’approuver ni la rejeter lui-même. Après un rejet, le créateur peut corriger la facture et la soumettre à nouveau ; l’historique conserve chaque version, décision, auteur, date et motif. L’approbation seule ne consomme pas le budget et ne permet pas de marquer la facture payée. Le budget consommé est mis à jour après validation comptable ; le parcours de paiement reste à définir. Le web et l’API partagent les mêmes validations, autorisations et services métier.

Voir [la revue d’architecture et les corrections](docs/ARCHITECTURE-REVIEW.md).

## Développement

Prérequis : PHP 8.2 avec les extensions Laravel, PDO MySQL, BCMath, XMLWriter et ZIP ; Composer 2 ; MySQL 8 ; Node 22.12 minimum. Tailwind 4 nécessite un navigateur moderne.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Configurer la connexion MySQL dans `.env`, puis :

```bash
php artisan migrate
npm run build
php artisan serve
```

Pour une base locale vide, `php artisan db:seed` initialise les données de démonstration. Ne pas utiliser ces comptes en production ; le seeder de démonstration refuse l’environnement `production`. Ne pas utiliser `migrate:fresh` sur une base à conserver.

Dans le cloud configuré, les équivalents PHP/Composer sont `/workspace/cloud-setup/php` et `/workspace/cloud-setup/composer`. `bash /workspace/cloud-setup/start.sh` démarre le runtime local, applique les migrations et vérifie la connexion. Les tests doivent utiliser la base séparée :

```bash
DB_DATABASE=gestion_test APP_ENV=testing /workspace/cloud-setup/php artisan test
npm run build
npm audit
```

En dehors du cloud, préparer une base de tests dédiée, régler les variables correspondantes et lancer `php artisan test`. Les tests utilisent `RefreshDatabase` : ne jamais cibler les données réelles.

Les écrans Blade utilisent des icônes Lucide SVG locales via le composant `<x-icon>` ; les sources sélectionnées et leur licence sont dans `resources/icons`. Les couleurs d’interface sont définies dans les vues avec une palette gris chaud, charbon et rouge ARTS.

## Justificatifs et API

Les nouveaux justificatifs sont stockés dans `storage/app/private` et servis par une route authentifiée qui contrôle l’accès à la dépense. Ne pas exposer ce répertoire via Nginx ou un lien public. Pour les anciens fichiers :

```bash
php artisan justificatifs:protect --dry-run
php artisan justificatifs:protect
```

La commande copie et compare les empreintes avant de modifier la référence et de supprimer l’ancienne copie publique. Elle refuse les conflits et signale les fichiers manquants. Sauvegarder les fichiers et la base avant cette migration.

Après la migration du schéma, calculer les empreintes des justificatifs déjà enregistrés :

```bash
php artisan justificatifs:empreintes
```

La commande peut être relancée ; elle signale les fichiers manquants. Les nouveaux justificatifs reçoivent une empreinte SHA-256 à l’enregistrement. Le registre fournisseurs est accessible à l’administrateur. La saisie reconnaît automatiquement un fournisseur existant malgré les variantes d’accents, d’espaces et de casse. Un même fournisseur avec le même numéro de facture normalisé, ou un justificatif déjà joint à une autre facture, bloque la soumission. Sur la fiche, un rapprochement de fournisseur, montant et date affiche une alerte à vérifier sans bloquer, car ces trois valeurs peuvent aussi correspondre à deux achats distincts. Les factures anciennes reçoivent un instantané de leur état actuel lors de la migration ; les décisions antérieures à cette migration ne peuvent pas être reconstruites.

Les montants agrégés de l’API sont des chaînes décimales exactes. L’étape d’approbation est `POST /api/depenses/{depense}/approve` avant la validation comptable. Les liens des justificatifs doivent passer par les routes protégées, jamais par `/storage/...` pour les nouveaux fichiers.

## Docker et production

Définir au minimum `APP_KEY` (stable), `APP_URL` (HTTPS), `MYSQL_PASSWORD` et `MYSQL_ROOT_PASSWORD` dans la configuration d’exécution. Ne pas incorporer ces valeurs aux images. Compose exige ces variables. Les bases existantes conservent leurs identifiants : modifier une variable MySQL ne change pas le mot de passe déjà enregistré dans la base.

Le build fournit les cibles `app` et `web` ; Nginx et PHP embarquent le même code. MySQL n’expose aucun port hôte. Le port HTTP Nginx est lié à `127.0.0.1:8080` par défaut ; placer un proxy HTTPS devant lui et renseigner ses adresses dans `TRUSTED_PROXIES`. Les cookies de session sont sécurisés. Vérifier ce montage en préproduction.

**Installation existante : migration du stockage avant démarrage.** L’ancien volume `app-data` contenait tout `/var/www` ; le nouveau volume `app-storage` ne contient que `/var/www/storage`. Identifier les noms effectifs des volumes avec `docker volume ls` et leur montage avec `docker inspect`, sauvegarder MySQL et l’ancien volume, arrêter les écritures, puis copier uniquement le contenu de son dossier `storage/` vers le nouveau volume de stockage. Préserver les justificatifs, fichiers privés et droits d’accès. Ne pas recopier l’ancien code ou son `vendor` sur la nouvelle image. Ne supprimer aucun ancien volume avant vérification et conservation d’une sauvegarde ; ne pas lancer `docker compose down -v`.

Après préparation des secrets et du stockage :

```bash
docker compose build
docker compose up -d
```

Le script d’entrée prépare les droits du stockage. Appliquer les migrations avec `docker compose exec --user www-data app php artisan migrate --force`. Exécuter ensuite la commande de protection des anciens fichiers dans le conteneur applicatif, puis tester les rôles, exports et uploads. Les migrations additives préservent les dépenses historiques ; leur retour arrière est refusé si cela détruirait des factures multi-articles ou les références privées.

Cette revue a validé la syntaxe Docker/Compose et le runtime de développement. La construction et le déploiement des images de production restent à vérifier en préproduction.
