FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar archivos al contenedor
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

# =========================================================
# LO QUE SE AGREGA SIN MODIFICAR LO ANTERIOR (Para MongoDB)
# =========================================================

# 1. Instalar dependencias para compilar librerías externas (unzip y git son para Composer)
RUN apt-get install -y libssl-dev unzip git

# 2. Descargar, compilar y activar la extensión oficial de MongoDB para PHP
RUN pecl install mongodb && docker-php-ext-enable mongodb

# 3. Descargar el binario de Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. Cambiar el directorio de trabajo a la raíz de la app y descargar los vendors
WORKDIR /var/www/html
RUN composer install --no-interaction --optimize-autoloader

EXPOSE 80
