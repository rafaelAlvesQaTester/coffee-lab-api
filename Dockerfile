FROM php:8.4-fpm-alpine

RUN apk add --no-cache libpq \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev libxml2-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" dom mbstring pdo_pgsql xml xmlwriter \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY src ./src
COPY tests ./tests
RUN composer install --no-interaction --no-progress --prefer-dist

COPY public ./public
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/99-coffee-lab.ini

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
