FROM php:8.2-apache

RUN a2enmod rewrite

RUN docker-php-ext-install pdo pdo_mysql mysqli

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN mkdir -p /var/www/html/logs /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads /var/www/html/logs /var/www/html/cache

EXPOSE 80
