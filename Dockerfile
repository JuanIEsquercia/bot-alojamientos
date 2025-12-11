FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install curl openssl

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar archivos
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod 600 /var/www/html/.env 2>/dev/null || true
RUN chmod 600 /var/www/html/firebase-credentials.json 2>/dev/null || true

# Puerto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]

