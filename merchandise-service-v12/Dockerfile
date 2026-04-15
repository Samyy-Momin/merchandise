FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    build-base \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath opcache intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add application user
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www

# Copy application
COPY . /var/www/html
COPY .env.example /var/www/html/.env

# Permissions
RUN chown -R www:www /var/www/html
RUN chmod -R 755 /var/www/html/storage

USER www

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN php artisan key:generate

EXPOSE 9000

CMD ["php-fpm"]
