FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev unzip git \
    && docker-php-ext-install intl mysqli pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

EXPOSE 8080
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8080"]
