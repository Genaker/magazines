#!/usr/bin/env sh
set -e

cd "$(dirname "$0")/.."

. "$(dirname "$0")/test-compose-env.sh"

COMPOSE="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker compose"
fi

if ! docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
  | grep -E "${COMPOSE_PROJECT_NAME}.*mariadb.*\(healthy\)" >/dev/null; then
  if docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
    | grep -E 'medium-clone.*mariadb.*\(healthy\)' >/dev/null; then
    echo "Test stack MariaDB not running — using dev stack (medium-clone, port 3307)."
    export COMPOSE_PROJECT_NAME=medium-clone
    export MARIADB_PORT=3307
  fi
fi

DB_PASSWORD="${DB_PASSWORD:-secret}"
DB_E2E_DATABASE="${DB_E2E_DATABASE:-magazines_e2e}"
DB_PORT="${MARIADB_PORT:-3307}"

echo "Starting MariaDB (if needed)..."

if docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
  | grep -E "${COMPOSE_PROJECT_NAME}.*mariadb.*\(healthy\)" >/dev/null; then
  echo "MariaDB already healthy."
else
  $COMPOSE up -d --no-recreate mariadb
fi

echo "Waiting for MariaDB..."
for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do
  if docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
    | grep -E "${COMPOSE_PROJECT_NAME}.*mariadb.*\(healthy\)" >/dev/null; then
    break
  fi
  if [ "$i" = 20 ]; then
    echo "MariaDB is not healthy. Check docker-compose logs mariadb."
    exit 1
  fi
  sleep 2
done

export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_PORT="$DB_PORT"
export DB_DATABASE="$DB_E2E_DATABASE"
export DB_USERNAME=root
export DB_PASSWORD="$DB_PASSWORD"

echo "Ensuring e2e database exists..."
php docker/ensure-test-database.php

echo "Running Playwright e2e (MariaDB)..."
exec npx playwright test -c tests/e2e/playwright.config.ts "$@"
