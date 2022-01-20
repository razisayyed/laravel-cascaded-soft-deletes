FROM php:8.1-cli

RUN apt-get update \
    && apt-get install -y git unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY . /app

WORKDIR /app

ENV XDEBUG_MODE=coverage

CMD [ "./vendor/bin/phpunit" ]


