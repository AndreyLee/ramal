# Use an official PHP image with Apache
FROM php:8.2-apache

# Set the working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    pkg-config \
    zlib1g-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        xml \
        intl \
        zip


# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy the public directory contents to the web server's document root
# This will make public/index.php available at /index.php
COPY public/ /var/www/html/

# Copy the src directory into a subdirectory within the document root (or another location)
# This keeps backend code separate from the immediate web root but accessible.
COPY src/ /var/www/html/src/

# Note: If src/ was meant to be outside /var/www/html for security,
# then include paths in PHP scripts would need to be adjusted,
# and potentially Apache config to alias or allow includes from that path.
# For simplicity, placing it inside /var/www/html/src makes it directly accessible
# for includes like `require_once __DIR__ . '/src/includes/file.php';` from `/var/www/html/index.php`

# Ensure the web server has write permissions to necessary directories if needed
# For example, if there were an 'uploads' directory:
# RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads
# For now, default permissions should be okay for session storage (usually /tmp or managed by PHP).

# Expose port 80
EXPOSE 80

# The default Apache CMD in the base image will start Apache.
# CMD ["apache2-foreground"]
