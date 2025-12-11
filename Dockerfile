FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install curl openssl

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar Apache para escuchar en puerto 8080 (Cloud Run)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

# Configurar DocumentRoot explícitamente
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar archivos
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod 600 /var/www/html/.env 2>/dev/null || true
RUN chmod 600 /var/www/html/firebase-credentials.json 2>/dev/null || true

# Puerto (Cloud Run usa 8080)
EXPOSE 8080

# Iniciar Apache
CMD ["apache2-foreground"]

