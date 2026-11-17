#!/bin/sh
set -eu

cd /var/www/app/web
if [ ! -f composer.lock ]; then
    echo 'composer.lock ontbreekt; webserver wordt niet gestart.' >&2
    exit 1
fi

echo 'Cards: composer install uitvoeren voor Apache start.'
su -s /bin/sh -c \
    'composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader && composer check-platform-reqs --no-dev' \
    app-installer

chown www-data:www-data /var/www/app/web/public/logos
chmod 0750 /var/www/app/web/public/logos

exec docker-php-entrypoint "$@"
