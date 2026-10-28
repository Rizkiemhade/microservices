FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 3003

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD php -r '$context = stream_context_create(["http" => ["timeout" => 2]]); exit(@file_get_contents("http://127.0.0.1:3003/up", false, $context) === false ? 1 : 0);'

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=3003"]
