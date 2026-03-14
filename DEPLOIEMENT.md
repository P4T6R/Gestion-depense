# 🚀 Guide de Déploiement Docker - ARTS Suivi Dépenses

## 📋 Prérequis sur le serveur 72.62.178.54

1. **Docker** installé
2. **Docker Compose** installé
3. **Git** installé
4. Accès SSH avec droits sudo
5. Ports ouverts: 80, 443, 3306, 8080

## 🔧 Installation de Docker (si nécessaire)

### Sur Ubuntu/Debian:
```bash
# Mettre à jour les paquets
sudo apt update

# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Installer Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Vérifier l'installation
docker --version
docker-compose --version
```

## 📦 Déploiement

### 1. Transférer le projet sur le serveur

**Option A: Via Git (recommandé)**
```bash
# Se connecter au serveur
ssh root@72.62.178.54

# Cloner le projet
cd /var/www
git clone <url-du-repo> suivi-depense
cd suivi-depense
```

**Option B: Via SCP depuis Windows**
```powershell
# Depuis votre PC Windows
scp -r "C:\Users\hp850 G4\Documents\Vrai project\suivi-depense_back" root@72.62.178.54:/var/www/suivi-depense
```

### 2. Configuration

```bash
# Se connecter au serveur
ssh root@72.62.178.54
cd /var/www/suivi-depense

# Copier le fichier .env de production
cp .env.production .env

# Modifier les paramètres si nécessaire
nano .env
```

**⚠️ IMPORTANT: Modifier ces valeurs dans .env**
- `DB_PASSWORD` - Changer le mot de passe MySQL
- `APP_URL` - Mettre l'IP ou le domaine réel

### 3. Lancer le déploiement

```bash
# Rendre le script exécutable
chmod +x deploy.sh

# Lancer le déploiement
./deploy.sh
```

Le script va automatiquement:
- ✅ Construire les images Docker
- ✅ Démarrer les conteneurs (App, Nginx, MySQL, phpMyAdmin)
- ✅ Générer la clé d'application
- ✅ Exécuter les migrations
- ✅ Insérer les données de base
- ✅ Optimiser Laravel
- ✅ Configurer les permissions

### 4. Vérifier le déploiement

```bash
# Voir les conteneurs en cours d'exécution
docker-compose ps

# Voir les logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f db
```

## 🌐 Accès à l'application

- **Application Web**: http://72.62.178.54
- **phpMyAdmin**: http://72.62.178.54:8080
- **Base de données**: 72.62.178.54:3306

## 👤 Comptes par défaut

Après le déploiement, connectez-vous avec:

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@arts.bf | password |
| Comptable | comptable@arts.bf | password |
| Directeur | directeur@arts.bf | password |
| Chef de chantier | chef@chantier.bf | password |

**⚠️ IMPORTANT**: Changez ces mots de passe immédiatement après la première connexion!

## 🛠️ Commandes utiles

### Gestion des conteneurs
```bash
# Démarrer tous les conteneurs
docker-compose up -d

# Arrêter tous les conteneurs
docker-compose down

# Redémarrer un conteneur spécifique
docker-compose restart app
docker-compose restart nginx

# Voir les logs en temps réel
docker-compose logs -f

# Reconstruire les images
docker-compose build --no-cache
```

### Maintenance Laravel
```bash
# Accéder au conteneur de l'application
docker-compose exec app bash

# Vider le cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# Exécuter les migrations
docker-compose exec app php artisan migrate

# Exécuter les seeders
docker-compose exec app php artisan db:seed
```

### Backup de la base de données
```bash
# Créer un backup
docker-compose exec db mysqldump -u suivi_user -p suivi_depense > backup_$(date +%Y%m%d).sql

# Restaurer un backup
docker-compose exec -T db mysql -u suivi_user -p suivi_depense < backup_20260130.sql
```

## 🔒 Sécurité

### Configuration du pare-feu (UFW)
```bash
# Installer UFW si nécessaire
sudo apt install ufw

# Autoriser SSH
sudo ufw allow 22/tcp

# Autoriser HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Bloquer l'accès direct à MySQL depuis l'extérieur
sudo ufw deny 3306/tcp

# Bloquer phpMyAdmin depuis l'extérieur (optionnel)
sudo ufw deny 8080/tcp

# Activer le pare-feu
sudo ufw enable
```

### HTTPS avec Let's Encrypt (optionnel)
```bash
# Installer Certbot
sudo apt install certbot

# Obtenir un certificat SSL (avec un nom de domaine)
sudo certbot certonly --standalone -d votre-domaine.com

# Modifier docker/nginx/default.conf pour ajouter SSL
```

## 📊 Monitoring

### Surveillance des ressources
```bash
# Voir l'utilisation des ressources par conteneur
docker stats

# Voir l'espace disque utilisé
docker system df
```

## 🔄 Mise à jour de l'application

```bash
# Se connecter au serveur
ssh root@72.62.178.54
cd /var/www/suivi-depense

# Récupérer les dernières modifications
git pull

# Reconstruire et redémarrer
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Exécuter les migrations
docker-compose exec app php artisan migrate --force

# Vider le cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan view:cache
```

## ⚠️ Problèmes courants

### L'application ne démarre pas
```bash
# Vérifier les logs
docker-compose logs app

# Vérifier les permissions
docker-compose exec app chmod -R 775 /var/www/storage
docker-compose exec app chown -R www-data:www-data /var/www/storage
```

### Erreur de connexion à la base de données
```bash
# Vérifier que MySQL est démarré
docker-compose ps db

# Vérifier les logs MySQL
docker-compose logs db

# Attendre que MySQL soit complètement démarré (15-30 secondes)
```

### Erreur 500 Internal Server Error
```bash
# Générer une nouvelle clé d'application
docker-compose exec app php artisan key:generate

# Vider tous les caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
```

## 📞 Support

Pour toute question ou problème, vérifiez les logs:
```bash
docker-compose logs -f
```

Bonne chance avec votre déploiement! 🚀
