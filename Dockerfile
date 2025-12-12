FROM php:8.2-apache

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar Apache para puerto 8080 (Cloud Run)
RUN echo 'Listen 8080' > /etc/apache2/ports.conf
RUN echo '<VirtualHost *:8080>\n\tDocumentRoot /var/www/html\n\t<Directory /var/www/html>\n\t\tAllowOverride All\n\t\tRequire all granted\n\t</Directory>\n</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copiar archivos
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Puerto
EXPOSE 8080

# Iniciar Apache
CMD ["apache2-foreground"]

# Iniciar Apache
CMD ["apache2-foreground"]

