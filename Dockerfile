FROM php:8.0-cli

RUN apt-get update -y && \
    apt-get install -y git zip unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

WORKDIR /app

CMD composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist ; \
    vendor/bin/nunjuck tests/cases -w src -w tests/cases -w tests/engine
