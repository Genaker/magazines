#!/bin/sh
set -e

cd /var/www/html

# Must run before PHP-FPM — background init used to leave MAIL_HOST=127.0.0.1 in .env
sync_docker_env() {
    if [ -z "${DB_HOST:-}" ] || [ "${DB_HOST}" = "127.0.0.1" ] || [ "${DB_HOST}" = "localhost" ]; then
        return 0
    fi

    php docker/sync-env.php
}

init_app() {
    if [ ! -f .env ]; then
        if [ -f .env.docker.example ]; then
            cp .env.docker.example .env
        else
            cp .env.example .env
        fi
    fi

    sync_docker_env

    if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
        php artisan key:generate --force --no-interaction
    fi

    if [ ! -f vendor/autoload.php ]; then
        echo "Missing vendor/. Run on host: composer install"
        exit 1
    fi

    if [ ! -f public/build/manifest.json ]; then
        echo "Missing frontend build. Run: docker-compose build app"
        echo "Or on the host: npm ci && npm run build"
        exit 1
    fi

    php artisan storage:link --force 2>/dev/null || true

    if [ -d storage/app/private/media ]; then
        mkdir -p storage/app/public/media
        cp -rn storage/app/private/media/. storage/app/public/media/ 2>/dev/null || true
        find storage/app/public/media -type d -exec chmod 755 {} + 2>/dev/null || true
        find storage/app/public/media -type f -exec chmod 644 {} + 2>/dev/null || true
    fi

    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
        mkdir -p database
        touch "${DB_DATABASE:-database/database.sqlite}"
    else
        echo "Waiting for MariaDB..."
        until php -r "
            try {
                new PDO(
                    'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
                    getenv('DB_USERNAME') ?: 'root',
                    getenv('DB_PASSWORD') ?: ''
                );
                exit(0);
            } catch (Throwable \$e) {
                exit(1);
            }
        " 2>/dev/null; do
            sleep 2
        done

        php -r '
            $db = preg_replace("/[^a-zA-Z0-9_]/", "", getenv("DB_DATABASE") ?: "magazines");
            $pdo = new PDO(
                "mysql:host=" . (getenv("DB_HOST") ?: "mariadb") . ";port=" . (getenv("DB_PORT") ?: "3306"),
                getenv("DB_USERNAME") ?: "root",
                getenv("DB_PASSWORD") ?: ""
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        ' 2>/dev/null || true
    fi

    if ! php artisan migrate --force --no-interaction; then
        echo "Migration failed — fix migrations before seeding."
        return 1
    fi

    USER_COUNT=$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n 1)
    if [ "${RUN_SEEDER:-true}" = "true" ] && [ "${USER_COUNT:-0}" = "0" ]; then
        php artisan db:seed --force --no-interaction
    fi

    if [ "${SEARCH_DRIVER:-database}" = "redis" ]; then
        php artisan search:reindex --no-interaction 2>/dev/null || true
    fi

    if [ "${SESSION_DOMAIN:-}" = ".lvh.me" ]; then
        php artisan tinker --execute='
            $host = \App\Models\SiteSetting::getValue("author_subdomain_base_host");
            if (in_array($host, [null, "", "localhost"], true)) {
                \App\Support\AuthorSubdomain::setBaseHost("lvh.me");
            }
        ' 2>/dev/null || true
    fi
}

if [ ! -f .env ] && [ "${INIT_APP:-false}" = "true" ]; then
    touch .env
fi

if [ -f .env ]; then
    sync_docker_env
fi

if [ "${INIT_APP:-false}" = "true" ]; then
    init_app &
fi

if [ "$1" = "php-fpm" ]; then
    exec php-fpm -F
fi

exec "$@"
