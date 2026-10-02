# Host ports and project name for the test Docker stack (PHPUnit / Playwright).
# Dev defaults in docker-compose.yml: magazines @ 8888, 3307, 5433, 6380, 8025, 1025.
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-magazines-test}"
export APP_PORT="${APP_PORT:-9888}"
export MARIADB_PORT="${MARIADB_PORT:-13307}"
export POSTGRES_PORT="${POSTGRES_PORT:-15433}"
export REDIS_PORT="${REDIS_PORT:-16380}"
export MAILPIT_UI_PORT="${MAILPIT_UI_PORT:-18025}"
export MAILPIT_SMTP_PORT="${MAILPIT_SMTP_PORT:-11025}"
