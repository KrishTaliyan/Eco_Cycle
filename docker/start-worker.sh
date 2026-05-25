#!/usr/bin/env bash
set -euo pipefail

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage
chmod -R ug+rwX bootstrap/cache storage

php artisan config:cache

exec php artisan queue:work --sleep=3 --tries=3 --timeout=90
