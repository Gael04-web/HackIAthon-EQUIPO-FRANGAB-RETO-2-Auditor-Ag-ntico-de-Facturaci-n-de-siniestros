# ==============================================================================
# DOCKERFILE PARA DESPLIEGUE EN DOKPLOY / DOCKER
# Auditor Agéntico de Facturación de Siniestros
# ==============================================================================

FROM php:8.2-apache

# Habilitar mod_rewrite de Apache para soportar enrutamiento limpio si fuera necesario
RUN a2enmod rewrite

# Evitar advertencias del ServerName en Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar dependencias del sistema y extensiones de PHP necesarias (curl y json ya están incluidas)
# Si en el futuro necesitas extensiones adicionales como pdo o zip:
# RUN apt-get update && apt-get install -y libzip-dev zip && docker-php-ext-install zip

# Copiar el código del proyecto al directorio público de Apache
COPY . /var/www/html/

# Ajustar permisos para que Apache pueda acceder a los archivos correctamente
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer el puerto 80 estándar
EXPOSE 80

# Comando por defecto para iniciar Apache en el contenedor
CMD ["apache2-foreground"]
