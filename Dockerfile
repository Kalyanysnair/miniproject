FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Enable Apache mod_rewrite (optional, for clean URLs)
RUN a2enmod rewrite

# Copy project files into the container
COPY . /var/www/html/
