FROM php:8.2-cli

# Install dependencies (optional)
RUN apt-get update && apt-get install -y unzip

# Copy all project files
COPY . /app
WORKDIR /app

# Install Composer (optional)
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Install PHP dependencies (skip errors if composer.json not present)
RUN composer install || true

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
