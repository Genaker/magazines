#!/usr/bin/env sh
# Restart web containers when http://127.0.0.1:8888 hangs or returns no response.
set -e
cd "$(dirname "$0")/.."

COMPOSE="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker compose"
fi

echo "Restarting nginx and app..."
$COMPOSE restart nginx app

echo "Waiting for app..."
for i in 1 2 3 4 5 6 7 8 9 10; do
  if curl -sf --max-time 5 http://127.0.0.1:8888/login >/dev/null 2>&1; then
    echo "OK — http://127.0.0.1:8888/login is responding."
    exit 0
  fi
  sleep 2
done

echo "Still not responding. Try: docker compose down && docker compose up -d --build"
exit 1
