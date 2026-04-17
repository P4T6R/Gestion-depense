#!/bin/bash

# Script de déploiement Docker pour ARTS Suivi Dépenses
# Serveur: 72.62.178.54

echo "=========================================="
echo "  Déploiement ARTS Suivi Dépenses Docker"
echo "=========================================="
echo ""

# Vérifier si Docker est installé
if ! command -v docker &> /dev/null; then
    echo "[ERREUR] Docker n'est pas installé sur ce serveur"
    echo "Installer Docker: https://docs.docker.com/engine/install/"
    exit 1
fi

# Vérifier si Docker Compose est installé
if ! command -v docker-compose &> /dev/null; then
    echo "[ERREUR] Docker Compose n'est pas installé sur ce serveur"
    echo "Installer Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi

echo "[OK] Docker et Docker Compose sont installés"
echo ""

# Arrêter les conteneurs existants
echo "🛑 Arrêt des conteneurs existants..."
docker-compose down

# Copier le fichier .env de production
if [ ! -f .env ]; then
    echo "[INFO] Création du fichier .env..."
    cp .env.production .env
    
    # Générer APP_KEY
    echo "🔑 Génération de la clé d'application..."
    docker-compose run --rm app php artisan key:generate
fi

# Construire les images Docker
echo "[BUILD] Construction des images Docker..."
docker-compose build --no-cache

# Démarrer les conteneurs
echo "[START] Démarrage des conteneurs..."
docker-compose up -d

# Attendre que MySQL soit prêt
echo "⏳ Attente du démarrage de MySQL..."
sleep 15

# Exécuter les migrations
echo "🗄️  Exécution des migrations..."
docker-compose exec -T app php artisan migrate --force

# Exécuter les seeders
echo "🌱 Exécution des seeders..."
docker-compose exec -T app php artisan db:seed --force

# Optimisations Laravel
echo "⚡ Optimisations Laravel..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
docker-compose exec -T app composer dump-autoload --optimize

# Créer le lien symbolique pour storage
echo "🔗 Création du lien symbolique storage..."
docker-compose exec -T app php artisan storage:link

# Permissions
echo "🔒 Configuration des permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker-compose exec -T app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo ""
echo "=========================================="
echo "[OK] Déploiement terminé avec succès!"
echo "=========================================="
echo ""
echo "🌐 Application: http://72.62.178.54"
echo "🗄️  phpMyAdmin: http://72.62.178.54:8080"
echo ""
echo "[INFO] Statut des conteneurs:"
docker-compose ps
echo ""
echo "[LOGS] Logs en temps réel:"
echo "   docker-compose logs -f"
echo ""
echo "🛑 Arrêter l'application:"
echo "   docker-compose down"
echo ""
