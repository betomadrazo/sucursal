FROM php:8.4-apache

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

RUN apt-get update && apt-get install -y libzip-dev unzip default-mysql-client \
    && docker-php-ext-install zip pdo pdo_mysql mysqli

RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]