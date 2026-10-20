#!/bin/sh
set -eu
cd /var/www/html

if [ "$1" = "apache2-foreground" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo 'Falta APP_KEY en .env.docker. Ejecuta bash docker/install.sh.' >&2
        exit 1
    fi
    mkdir -p storage/app/public storage/app/private storage/fonts storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    php artisan migrate --force --no-interaction
    php artisan config:cache
    php artisan view:cache
    chown -R www-data:www-data storage bootstrap/cache
fi
exec "$@"
