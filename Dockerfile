FROM php:8.2-apache

# Copy project files into the container
COPY . /var/www/html/

# Enable Apache mod_rewrite (optional, for clean URLs)
RUN a2enmod rewrite
