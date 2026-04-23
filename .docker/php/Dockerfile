FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
