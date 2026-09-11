#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

# serve the Laravel public directory as Apache document root
sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf
echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
a2enconf servername >/dev/null 2>&1 || true

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache public/aimeos
chown -R www-data:www-data storage bootstrap/cache public/aimeos

if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
    echo "=== Deriving DB connection settings from DATABASE_URL ==="
    eval "$(php -r '
        $u = getenv("DATABASE_URL");
        $p = parse_url($u);
        $out = array(
            "DB_HOST" => isset($p["host"]) ? $p["host"] : "127.0.0.1",
            "DB_PORT" => isset($p["port"]) ? $p["port"] : "5432",
            "DB_DATABASE" => ltrim(isset($p["path"]) ? $p["path"] : "", "/"),
            "DB_USERNAME" => rawurldecode(isset($p["user"]) ? $p["user"] : ""),
            "DB_PASSWORD" => rawurldecode(isset($p["pass"]) ? $p["pass"] : ""),
        );
        foreach($out as $k => $v) { echo "export $k=" . escapeshellarg($v) . "\n"; }
    ')"
fi

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
        # The demo setup tasks delete and re-create all "demo-*" catalog nodes,
        # which changes their IDs on every boot and breaks the category URLs.
        # Seed them only once; afterwards run setup without the demo option so
        # the schema stays up-to-date but the catalog data keeps stable IDs.
        demo_count="$(DATABASE_URL="$DATABASE_URL" php -r '
            $u = getenv( "DATABASE_URL" );
            $p = parse_url( $u );
            $dsn = "pgsql:host=" . ( $p["host"] ?? "127.0.0.1" ) . ";port=" . ( $p["port"] ?? "5432" ) . ";dbname=" . ltrim( $p["path"] ?? "", "/" );
            $pdo = new PDO( $dsn, rawurldecode( $p["user"] ?? "" ), rawurldecode( $p["pass"] ?? "" ) );
            $sql = "SELECT COUNT(*) FROM mshop_catalog WHERE code LIKE " . $pdo->quote( "demo-%" ) . " AND level = 1";
            echo (int) $pdo->query( $sql )->fetchColumn();
        ' 2>/dev/null || true)"

        if [ "${demo_count:-0}" != "0" ]; then
            php artisan aimeos:setup
        else
            php artisan aimeos:setup --option=setup/default/demo:1
        fi
        php artisan aimeos:clear
        php artisan asaan:setup

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

# The admin panel's JS bundle references an undeclared "Markdown" CKEditor
# plugin, which aborts registration of the map/chart widgets and breaks the
# admin UI. It is disabled (markdown:false) anyway, so drop it from the list.
f=$(find vendor/aimeos/ai-admin-jqadm -name vendor.js -type f | head -1 || true)
if [ -z "$f" ]; then
    echo "ERROR: admin vendor.js not found for patch"
    exit 1
fi
sed -i 's/TH,Markdown,LH/TH,LH/' "$f"
if grep -q 'TH,Markdown,LH' "$f"; then
    echo "ERROR: admin vendor.js patch did not apply: $f"
    exit 1
fi
echo "Admin vendor.js patched: $f"

chown -R www-data:www-data storage bootstrap/cache public/aimeos

exec apache2-foreground