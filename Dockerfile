FROM php:8.3-cli
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd bcmath \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
EXPOSE 8000
WORKDIR /var/www/public
CMD ["php", "-S", "0.0.0.0:8000", "../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
