# Getting Started

## Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, PHP 8.4+ |
| Frontend | Blade, Tailwind CSS v4, SCSS, Vite, Alpine.js |
| Editor | TinyMCE |
| Database | MariaDB (Laravel `mysql` driver) |
| Cache / sessions | Redis (`SESSION_DRIVER=redis`) |
| Email (dev) | Mailpit (single instance, port 8025) |
| Tests | PHPUnit, Playwright |

## Docker (recommended)

```bash
docker-compose up -d --build
```

| Service | URL / connection |
|---------|------------------|
| App | **http://127.0.0.1:8888** (recommended) or **http://lvh.me:8888** — port **`:8888` is required** (Docker maps `8888→80`) |
| Register (manual test) | http://127.0.0.1:8888/register |
| Login / PIN step | http://127.0.0.1:8888/login |
| Admin | **http://127.0.0.1:8888/admin/login** — magic link (default) or `/admin/login/password`; separate from site `/login` |
| Mailpit | http://127.0.0.1:8025 |
| MariaDB | `127.0.0.1:3307`, user `root`, password `secret`, db `magazines` |
| Redis | `127.0.0.1:6380` — **Redis Stack** (cache, sessions, RediSearch module) |

**Quick check:**

```bash
curl -I --max-time 10 http://127.0.0.1:8888/login   # expect HTTP/1.1 200 within a few seconds
docker compose ps                                   # nginx + app + mariadb should be Up
```

### Troubleshooting: site can't be reached

| Symptom | Fix |
|---------|-----|
| `lvh.me` does not load | Use **http://127.0.0.1:8888** instead (`lvh.me` needs DNS → `127.0.0.1`; some networks block it) |
| Connection refused on `:8888` | Start Docker: `docker compose up -d` from the project root |
| Page hangs / never loads | PHP-FPM may be stuck — restart: `docker compose restart nginx app` or `./scripts/docker-restart.sh` |
| Still broken after restart | Full reset: `docker compose down && docker compose up -d --build` |

**Author / magazine subdomains (optional):** use **`http://lvh.me:8888`** as the main site and **`http://the-commons.lvh.me:8888/`** for magazines — see [Site settings & CLI](24-site-settings.md). Old `*.localhost` bookmarks redirect to `*.lvh.me` automatically when base host is `lvh.me`. For subdomain testing without `lvh.me`, see [Author profiles → Browser support](11-author-profiles.md).

**Stay logged in across subdomains:** sign in on `http://lvh.me:8888`, set base host to `lvh.me`, use `SESSION_DOMAIN=.lvh.me` — see [Troubleshooting: logged out on Profile or magazine subdomain](24-site-settings.md#troubleshooting-logged-out-on-profile-or-magazine-subdomain).

In local debug mode (`APP_DEBUG=true`), magic-link sign-in codes and email-verification links are also shown on the login/verify pages — see [Authentication → Local debug helpers](02-authentication.md#local-debug-helpers-app_debugtrue).

Mailpit runs **only** inside Docker — do not start a second Mailpit on the host.

If port 8025 is already in use:

```bash
pkill -f mailpit
docker-compose up -d mailpit
```

Emails are sent **synchronously** (no queue worker).

After pulling code that adds schema migrations, apply them to the running Docker database:

```bash
./scripts/docker-artisan.sh migrate --force
```

The app entrypoint also runs `migrate` on container start; a one-off `migrate` is enough after schema changes without restarting.

The **scheduler** service runs `php artisan schedule:work`, which executes all tasks from **`config/schedule.php`** through one entry point — see [Scheduler](21-scheduler.md).

Requires `vendor/` and `public/build/` on the host before first run.

### Split cache and search Redis (optional)

By default one Redis Stack instance handles cache, sessions, and (future) search indexes. To use a plain Redis for cache and a second Redis Stack only for search:

```bash
docker compose -f docker-compose.yml -f docker-compose.split-redis.yml up -d
```

Set `REDIS_SEARCH_HOST=redis-search` (done automatically in the split compose file). See `config/search.php` and `REDIS_SEARCH_*` in `.env.example`.

### Web installer

If the database is empty (no users), open the app in a browser — you are redirected to **`/install`** for a WordPress-style setup wizard (requirements, database, site name, super-admin account). See [Web installer](22-installer.md).

Docker still auto-migrates and seeds demo data on first boot when `RUN_SEEDER=true` (default).

## Local development (without Docker)

For scheduled tasks (subscription digest, stats snapshot, scheduled publish), run in a separate terminal:

```bash
php artisan schedule:work
```

See [Scheduler](21-scheduler.md) for all CLI commands.

### Prerequisites

- PHP 8.4+ (`pdo_mysql`, `redis`, `mbstring`, `xml`, `curl`, `fileinfo`)
- Composer, Node.js 20+, npm
- MariaDB or MySQL — `CREATE DATABASE magazines;`
- Redis on `127.0.0.1:6379`
- Mailpit: `brew install mailpit`, then run `mailpit` (UI http://127.0.0.1:8025)

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm ci && npm run build
```

### Run

```bash
composer dev    # terminal 1
mailpit         # terminal 2 — only if Docker Mailpit is not running
```

| Service | URL |
|---------|-----|
| App | http://127.0.0.1:8000 |
| Mailpit | http://127.0.0.1:8025 |

## Related docs

- [Styling](18-styling.md) — Tailwind, SCSS, `npm run build`
- [Authentication](02-authentication.md)
- [Testing](15-testing.md)
