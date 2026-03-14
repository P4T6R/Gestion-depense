FROM nginx:alpine

# Créer le répertoire pour les fichiers de l'application
RUN mkdir -p /var/www/public

# Copier la configuration Nginx
COPY default.conf /etc/nginx/conf.d/default.conf

# Exposer le port 80
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
