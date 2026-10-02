#!/usr/bin/env sh
# Quick check that lvh.me subdomains reach Docker nginx (port 8888 by default).
set -e

PORT="${APP_PORT:-8888}"
FAIL=0

check() {
  url="$1"
  expected_title="$2"
  if ! code=$(curl -s -o /tmp/subdomain-check.html -w "%{http_code}" --connect-timeout 3 "$url"); then
    printf "FAIL  %s — connection failed (is Docker running? use :%s)\n" "$url" "$PORT"
    FAIL=1
    return
  fi
  if [ "$code" != "200" ] && [ "$code" != "301" ] && [ "$code" != "302" ]; then
    printf "FAIL  %s — HTTP %s\n" "$url" "$code"
    FAIL=1
    return
  fi
  if [ -n "$expected_title" ]; then
    title=$(grep -o '<title>[^<]*</title>' /tmp/subdomain-check.html 2>/dev/null | head -1 || true)
    if [ "$title" != "<title>${expected_title}</title>" ]; then
      printf "WARN  %s — HTTP %s but title is %s (expected %s)\n" "$url" "$code" "$title" "$expected_title"
      FAIL=1
      return
    fi
  fi
  printf "OK    %s — HTTP %s\n" "$url" "$code"
}

echo "Checking local subdomain URLs (port ${PORT})..."
echo ""

check "http://lvh.me:${PORT}/" "First"
check "http://the-commons.lvh.me:${PORT}/" "The Commons"

echo ""
if curl -s --connect-timeout 2 -o /dev/null "http://the-commons.lvh.me/" 2>/dev/null; then
  echo "Note: http://the-commons.lvh.me/ (no port) responds — port 80 is mapped."
else
  echo "Note: http://the-commons.lvh.me/ without :${PORT} will NOT work unless nginx is on port 80."
  echo "      Always use :${PORT} in the browser, e.g. http://the-commons.lvh.me:${PORT}/"
fi

echo ""
if command -v docker >/dev/null 2>&1; then
  CONTAINER=$(docker ps --format '{{.Names}}' | grep -E 'medium-clone.*app|medium-clone_app' | head -1 || true)
  if [ -n "$CONTAINER" ]; then
    echo "Docker site settings:"
    docker exec "$CONTAINER" php artisan site:setting get author_subdomain_base_host 2>/dev/null || true
    docker exec "$CONTAINER" php artisan site:setting get magazine_subdomains 2>/dev/null || true
    docker exec "$CONTAINER" grep -E '^(APP_URL|SESSION_DOMAIN)=' .env 2>/dev/null || true
    echo ""
    echo "Runtime (what PHP actually uses):"
    docker exec "$CONTAINER" php artisan config:show app.url 2>/dev/null || true
    docker exec "$CONTAINER" php artisan config:show session.domain 2>/dev/null || true
    RUNTIME_URL=$(docker exec "$CONTAINER" php artisan config:show app.url 2>/dev/null | grep -oE 'https?://[^[:space:]]+' | head -1 || true)
    ENV_URL=$(docker exec "$CONTAINER" printenv APP_URL 2>/dev/null || true)
    EXPECTED="http://lvh.me:${PORT}"
    if [ "$RUNTIME_URL" != "$EXPECTED" ]; then
      echo ""
      echo "WARN  app.url is $RUNTIME_URL (expected $EXPECTED)."
      echo "      Check .env APP_URL and author_subdomain_base_host (lvh.me)."
      echo "      Sign in only on $EXPECTED — not 127.0.0.1 or localhost."
      FAIL=1
    elif [ -n "$ENV_URL" ] && [ "$ENV_URL" != "$EXPECTED" ]; then
      echo ""
      echo "WARN  Container APP_URL=$ENV_URL overrides .env — recreate app:"
      echo "      docker-compose up -d --no-deps app"
      FAIL=1
    fi
  fi
fi

rm -f /tmp/subdomain-check.html
exit "$FAIL"
