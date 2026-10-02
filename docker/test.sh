#!/usr/bin/env sh
set -e

cd "$(dirname "$0")/.."

. "$(dirname "$0")/test-compose-env.sh"

COMPOSE="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker compose"
fi

# Prefer magazines-test (13307). Fall back to the dev stack when test MariaDB is stopped.
if ! docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
  | grep -E "${COMPOSE_PROJECT_NAME}.*mariadb.*\(healthy\)" >/dev/null; then
  if docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
    | grep -E 'medium-clone.*mariadb.*\(healthy\)' >/dev/null; then
    echo "Test stack MariaDB not running — using dev stack (medium-clone, port 3307)."
    export COMPOSE_PROJECT_NAME=medium-clone
    export MARIADB_PORT=3307
    export APP_PORT=8888
    export REDIS_PORT=6380
  fi
fi

TEST_DB="${TEST_DB:-mysql}"
DB_PASSWORD="${DB_PASSWORD:-secret}"
DB_TEST_DATABASE="${DB_TEST_DATABASE:-magazines_test}"

wait_for_mariadb() {
  echo "Starting MariaDB (if needed)..."

  if docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
    | grep -E "${COMPOSE_PROJECT_NAME}.*mariadb.*\(healthy\)" >/dev/null; then
    echo "MariaDB already healthy."
    return 0
  fi

  $COMPOSE up -d --no-recreate mariadb

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
}

wait_for_postgres() {
  POSTGRES_USER="${POSTGRES_USERNAME:-magazines}"
  POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-secret}"

  POSTGRES_IMAGE="${POSTGRES_IMAGE:-magazines-postgres:16}"

  echo "Starting PostgreSQL (if needed)..."
  if [ "$POSTGRES_IMAGE" = "magazines-postgres:16" ]; then
    $COMPOSE build postgres
  else
    echo "Using external Postgres image: $POSTGRES_IMAGE (skip build)"
    $COMPOSE pull postgres 2>/dev/null || true
  fi
  $COMPOSE up -d postgres

  echo "Waiting for PostgreSQL..."
  for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do
    if $COMPOSE exec -T postgres pg_isready -U "$POSTGRES_USER" -d postgres >/dev/null 2>&1; then
      break
    fi
    if [ "$i" = 20 ]; then
      echo "PostgreSQL is not healthy. Check docker-compose logs postgres."
      exit 1
    fi
    sleep 2
  done

  echo "Ensuring test database exists..."
  $COMPOSE exec -T -e PGPASSWORD="$POSTGRES_PASSWORD" postgres psql -U "$POSTGRES_USER" -d postgres -tc \
    "SELECT 1 FROM pg_database WHERE datname = '$DB_TEST_DATABASE'" | grep -q 1 \
    || $COMPOSE exec -T -e PGPASSWORD="$POSTGRES_PASSWORD" postgres psql -U "$POSTGRES_USER" -d postgres -c \
    "CREATE DATABASE \"$DB_TEST_DATABASE\""
}

wait_for_redis() {
  echo "Starting Redis Stack (if needed)..."

  if docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
    | grep -E "${COMPOSE_PROJECT_NAME}.*redis" >/dev/null; then
    echo "Redis already running."
    return 0
  fi

  $COMPOSE up -d --no-recreate redis

  echo "Waiting for Redis Stack..."
  for i in 1 2 3 4 5 6 7 8 9 10 11 12; do
    if docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null \
      | grep -E "${COMPOSE_PROJECT_NAME}.*redis" >/dev/null; then
      break
    fi
    if [ "$i" = 12 ]; then
      echo "Redis Stack is not running. Ensure docker-compose.yml redis has security_opt: seccomp=unconfined."
      exit 1
    fi
    sleep 2
  done
}

case "$TEST_DB" in
  mysql|mariadb)
    wait_for_mariadb
    DB_CONNECTION=mysql
    DB_HOST=mariadb
    DB_PORT=3306
    DB_USERNAME=root
    DB_PASSWORD="$DB_PASSWORD"
    DB_LABEL="MariaDB"
    ;;
  pgsql|postgres|postgresql)
    wait_for_postgres
    DB_CONNECTION=pgsql
    DB_HOST=postgres
    DB_PORT=5432
    DB_USERNAME="${POSTGRES_USERNAME:-magazines}"
    DB_PASSWORD="${POSTGRES_PASSWORD:-secret}"
    DB_LABEL="PostgreSQL"
    ;;
  *)
    echo "Unsupported TEST_DB: $TEST_DB (use mysql or pgsql)"
    exit 1
    ;;
esac

wait_for_redis

if docker image inspect "${COMPOSE_PROJECT_NAME}_app" >/dev/null 2>&1 \
  || docker image inspect "medium-clone_app" >/dev/null 2>&1; then
  echo "App image already built."
else
  echo "Ensuring app image is built (GD + phpredis + pdo_pgsql)..."
  $COMPOSE build app
fi

echo "Running PHPUnit in Docker (app image, $DB_LABEL)..."
$COMPOSE run --rm --no-deps \
  -e APP_ENV=testing \
  -e INIT_APP=false \
  -e DB_CONNECTION="$DB_CONNECTION" \
  -e DB_HOST="$DB_HOST" \
  -e DB_PORT="$DB_PORT" \
  -e DB_DATABASE="$DB_TEST_DATABASE" \
  -e DB_USERNAME="$DB_USERNAME" \
  -e DB_PASSWORD="$DB_PASSWORD" \
  -e CACHE_STORE=array \
  -e ENTITY_CACHE_STORE=array \
  -e SEARCH_DRIVER=database \
  -e SESSION_DRIVER=array \
  -e QUEUE_CONNECTION=sync \
  -e MAIL_MAILER=array \
  -e REDIS_CLIENT=phpredis \
  -e REDIS_HOST=redis \
  -e REDIS_PORT=6379 \
  -e REDIS_SEARCH_DB=0 \
  --entrypoint "" \
  app sh -c 'php docker/ensure-test-database.php && exec vendor/bin/phpunit "$@"' _ "$@"
