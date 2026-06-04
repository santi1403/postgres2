FROM php:8.2-apache

# 1. Actualizar e instalar dependencias de Postgres Y MongoDB (libssl y git son para Mongo/Composer)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# 2. Instalar Composer de forma global dentro del contenedor
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Habilitar mod_rewrite (lo mantenemos tal cual lo tenías)
RUN a2enmod rewrite

# 3. Copiar todos los archivos del proyecto al contenedor
COPY . /var/www/html/

# 4. Cambiar al directorio de trabajo para poder correr comandos dentro de él
WORKDIR /var/www/html/

# 5. Ejecutar Composer para crear la carpeta vendor y el autoload.php que faltaba
RUN composer install --no-interaction --optimize-autoloader

# Permisos (mantenemos tu regla de permisos)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
