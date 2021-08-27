FROM php:5.6.40-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
 && apt-get install --no-install-recommends -y git unzip \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
