FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq5 libxml2 libonig5 unzip \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS libpq-dev libxml2-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" dom mbstring pdo_pgsql xml xmlwriter \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS libpq-dev libxml2-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/*

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
