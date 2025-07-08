FROM php:8.2-cli-alpine

LABEL org.opencontainers.image.source=https://github.com/jesperbeisner/warehouse

ENV COMPOSER_ALLOW_SUPERUSER=1

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions zip @composer

RUN apk update && apk add --no-cache chromium chromium-chromedriver

WORKDIR /var/www/warehouse

COPY . .

RUN composer install

CMD ["php", "index.php"]
