FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    curl \
    nano

RUN docker-php-ext-install intl mbstring opcache pcntl zip

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install mongodb-2.0.0 \
    && docker-php-ext-enable mongodb \
    && apk del .build-deps

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-bioreport.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

USER www-data

EXPOSE 9000
