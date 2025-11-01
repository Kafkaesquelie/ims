# Use official PHP 8.4 image with Apache
FROM php:8.4-apache

# Set working directory
WORKDIR /var/www/html

# Install system packages & PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql mbstring exif bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy application files
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies via Composer
RUN composer install --no-dev --optimize-autoloader || true

# Ensure uploads directory exists and is writable
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Override Apache config for rewrite rules
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Expose default web port
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
