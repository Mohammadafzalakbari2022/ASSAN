#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache public/aimeos
chown -R www-data:www-data storage bootstrap/cache public/aimeos

if [ "${RUN_SETUP:-true}" = "true" ]; then
    echo "=== ASSAN database setup ==="

    migrated=0
    i=0
    while [ ${i} -lt 10 ]; do
        if php artisan migrate --force --no-interaction; then
            migrated=1
            break
        fi
        i=$((i+1))
        echo "Database not ready yet, retry ${i}/10 in 10s"
        sleep 10
    done

    if [ ${migrated} -eq 1 ]; then
        php artisan aimeos:setup --option=setup/default/demo:1
        php artisan aimeos:clear

        if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
            php artisan aimeos:account --super "${ADMIN_EMAIL}" --password "${ADMIN_PASSWORD}"
        else
            echo "=== ADMIN_EMAIL/ADMIN_PASSWORD not set, no admin account created ==="
        fi
    else
        echo "=== Database is not reachable, aborting ==="
        exit 1
    fi

    echo "=== ASSAN database setup finished ==="
fi

if [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ] && [[ "${APP_URL:-}" != http* ]]; then
    export APP_URL="https://${RENDER_EXTERNAL_HOSTNAME}"
    echo "=== APP_URL set to ${APP_URL} ==="
fi

php artisan config:cache || true
php artisan view:cache || true

chown -R www-data:www-data storage bootstrap/cache public/aimeos

exec apache2-foreground