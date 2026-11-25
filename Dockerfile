# Backend Laravel. Sirve igual en local (docker compose) y en la nube (Render).
FROM php:8.3-cli
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd bcmath \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Respuestas comprimidas con GZIP cuando el navegador lo acepta
RUN printf 'zlib.output_compression=On\nzlib.output_compression_level=6\nmemory_limit=256M\n' > /usr/local/etc/php/conf.d/produccion.ini

WORKDIR /var/www
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chmod -R 777 storage bootstrap/cache \
    && chmod +x docker-start.sh

ENV PORT=8000
EXPOSE 8000
CMD ["./docker-start.sh"]
