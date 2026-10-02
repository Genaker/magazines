#!/usr/bin/env sh
# Run artisan inside the local Docker app container (medium-clone stack).
set -e

CONTAINER=$(docker ps --format '{{.Names}}' | grep -E 'medium-clone.*app|medium-clone_app' | head -1)

if [ -z "$CONTAINER" ]; then
  echo "No medium-clone app container running. Start with: docker-compose up -d" >&2
  exit 1
fi

exec docker exec "$CONTAINER" php artisan "$@"
