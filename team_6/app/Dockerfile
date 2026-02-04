FROM php:8.2-fpm

# Install PostgreSQL PDO extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /var/www/html

# Copy source code
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html
