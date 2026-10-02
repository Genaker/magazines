#!/usr/bin/env sh
set -e

cd "$(dirname "$0")/.."

COMPOSE="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker compose"
fi

$COMPOSE run --rm --no-deps \
  -e PHPSTAN_DISABLE_XDEBUG_HANDLER=1 \
  --entrypoint "" \
  app \
  vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=512M "$@"
