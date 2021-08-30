FROM php:5.6.40-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
 && apt-get install --no-install-recommends -y git zip unzip gettext \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && curl -sLo /usr/local/bin/dist.sh https://git.io/dist.sh && chmod +x /usr/local/bin/dist.sh \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
