FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libsqlite3-dev unzip git \
    && docker-php-ext-install intl mysqli pdo_mysql pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

EXPOSE 8080
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8080"]
