#!/bin/sh
set -eu

cd /var/www/html
# storage/app is a persistent volume: create its folders on a fresh one (uploads live in storage/app/private).
mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

# Build caches only after runtime secrets and configuration have been injected.
php artisan package:discover --ansi
if [ "${1:-}" = "apache2-foreground" ]; then
    : "${APP_KEY:?Inject a stable APP_KEY before starting the web container}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec docker-php-entrypoint "$@"
