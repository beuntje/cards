FROM composer:2 AS composer

FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod headers rewrite \
    && groupadd --gid 10001 app-installer \
    && useradd --uid 10001 --gid app-installer --no-create-home app-installer \
    && mkdir -p /var/www/app/web/vendor /var/www/app/web/public/logos /tmp/composer \
    && chown app-installer:app-installer /var/www/app/web/vendor /tmp/composer

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/webserver/apache.conf /etc/apache2/sites-available/000-default.conf
COPY --chmod=0755 docker/webserver/entrypoint.sh /usr/local/bin/cards-entrypoint
COPY --chown=app-installer:app-installer web/composer.json web/composer.lock /var/www/app/web/
COPY --chown=app-installer:app-installer web/src /var/www/app/web/src
COPY --chown=app-installer:app-installer web/templates /var/www/app/web/templates
COPY web/public /var/www/app/web/public
COPY --chown=app-installer:app-installer web/bin /var/www/app/web/bin

ENV COMPOSER_HOME=/tmp/composer
WORKDIR /var/www/app/web

ENTRYPOINT ["cards-entrypoint"]
CMD ["apache2-foreground"]
