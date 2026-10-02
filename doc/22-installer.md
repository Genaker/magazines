# Web installer

## Overview

When the database has **no users**, the app redirects all web traffic to a WordPress-style setup wizard at **`/install`**. After setup, the installer is locked and normal routes work.

| | |
|---|---|
| **URL** | `/install` |
| **Available when** | `users` table missing or empty |
| **Blocked when** | At least one user exists (including `db:seed`) |

PHPUnit skips the install gate by default so existing feature tests keep working. `AppInstallerTest` enables it explicitly.

## Setup flow

1. **Requirements** — PHP 8.3+, extensions, writable `storage/` and `bootstrap/cache/`, `vendor/`, `.env`
2. **Database** — test connection; save credentials to `.env` if needed (MySQL/MariaDB or PostgreSQL)
3. **Site & admin** — site name, tagline, footer text, theme colors, home layout, languages, super-admin account; optional **Seed demo data** checkbox
4. **Finish** — runs migrations in-process via `DatabaseMigrator` (pure PHP, no shell/Artisan), saves branding to `site_settings` and `.env`, seeds feature defaults, logs you in, redirects to **Admin → Settings**

Branding defaults live in **`config/site.php`** and can be preset in `.env` before install:

| Variable | Purpose |
|----------|---------|
| `SITE_NAME` | Site title (also `APP_NAME`) |
| `SITE_TAGLINE` | Meta description / PWA text |
| `SITE_FOOTER_TAGLINE` | Footer headline |
| `SITE_FOOTER_RIGHTS` | Footer rights phrase |
| `SITE_COPYRIGHT_START` | Copyright start year |
| `SITE_THEME_COLOR` | PWA theme color |
| `SITE_BACKGROUND_COLOR` | PWA background |
| `SITE_HOME_LAYOUT` | `discover`, `latest`, or `trending` |
| `SITE_DEFAULT_LOCALE` | Default language code |
| `SITE_ENABLED_LOCALES` | Comma-separated locale codes |

Docker `entrypoint.sh` writes these into `.env` on first boot; the seeder calls `SiteBranding::seedDefaults()` when demo data is loaded.

The installer generates `APP_KEY` and `storage:link` if missing.

## Manual setup (alternative)

### CLI commands

| Command | Purpose |
|---------|---------|
| `php artisan app:install` | Migrate, branding, super-admin (non-interactive flags supported) |
| `php artisan app:install:status` | Requirements, DB connection, installed state |
| `php artisan app:migrate` | Pending migrations via `DatabaseMigrator` (in-process) |
| `php artisan app:user:create` | Add author (`--role=user` or `author`), admin, or super-admin |
| `php artisan app:seed-demo` | Rich demo content (posts, gallery, video, magazine, sample authors) |

**First-time install (uses `.env` DB/Redis when set, defaults for site/admin, generates password):**

```bash
cp .env.example .env
php artisan app:install --no-interaction
```

**First-time install (explicit options):**

```bash
cp .env.example .env
php artisan app:install \
  --no-interaction \
  --site-name="My Blog" \
  --name="Site Owner" \
  --username=owner \
  --email=owner@example.com \
  --password=secret \
  --locales=en \
  --locale-default=en \
  --db-connection=mysql \
  --db-host=127.0.0.1 \
  --db-port=3307 \
  --db-database=magazines \
  --db-username=root \
  --db-password=secret \
  --redis-host=127.0.0.1 \
  --redis-port=6379
```

Use `--skip-redis` for file cache and database search instead of Redis Stack.

**CLI flags (mirror the web installer):**

| Flag | Purpose |
|------|---------|
| `--site-name`, `--site-tagline`, `--footer-*`, `--theme-color`, `--background-color` | Branding |
| `--home-layout`, `--locales`, `--locale-default` | Home page and languages |
| `--name`, `--username`, `--email`, `--password` | Super-admin (`--password` optional; generated when omitted) |
| `--app-url` | `APP_URL` in `.env` |
| `--db-connection`, `--db-host`, `--db-port`, `--db-database`, `--db-username`, `--db-password` | Database (saved to `.env` when any `--db-*` is passed) |
| `--redis-host`, `--redis-port`, `--redis-password` | Redis (saved when any `--redis-*` is passed) |
| `--skip-redis` | File sessions/cache and database search |
| `--seed-demo` | ~12 posts with cover images (GD), photo gallery, video story, magazine with logo, 3 demo authors (password `password`) |

When `--db-*` / `--redis-*` are omitted, existing `.env` values are used. Omitted admin fields default to `Administrator` / `admin` / `admin@magazines.test`.

**Add an author after install:**

```bash
php artisan app:user:create \
  --no-interaction \
  --role=author \
  --name="Demo Author" \
  --username=demoauthor \
  --email=author@example.com \
  --password=secret
```

**Local dev with demo content** (alternative to Docker auto-seed):

```bash
php artisan app:seed-demo
```

Demo accounts: `author@magazines.test`, `reporter@magazines.test`, `tech@magazines.test` (password `password`). When GD is available, cover images, avatars, gallery slides, and a magazine logo are generated automatically.

Or the classic Laravel path:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Security

- Installer routes use the `app.not_installed` middleware — redirect to home once installed
- All other web routes use `app.installed` — redirect to `/install` when not installed
- `/up` health check is always available
- `.env` updates are limited to whitelisted keys (`DB_*`, `APP_NAME`, `APP_URL`)

## Key files

| File | Role |
|------|------|
| `app/Console/Commands/AppInstallCommand.php` | `app:install` |
| `app/Console/Commands/CreateUserCommand.php` | `app:user:create` |
| `app/Console/Commands/AppInstallStatusCommand.php` | `app:install:status` |
| `app/Console/Commands/AppMigrateCommand.php` | `app:migrate` |
| `app/Console/Commands/SeedDemoCommand.php` | `app:seed-demo` |
| `app/Support/UserProvisioner.php` | Shared user + alias creation |
| `app/Support/DatabaseMigrator.php` | In-process migration runner (uses Laravel `Migrator`, not CLI) |
| `app/Support/EnvWriter.php` | Safe `.env` updates |
| `app/Http/Controllers/InstallController.php` | Wizard UI |
| `app/Http/Middleware/EnsureAppIsInstalled.php` | Gate main app |
| `app/Http/Middleware/EnsureAppNotInstalled.php` | Lock installer after setup |
| `routes/install.php` | Installer routes |
| `config/site.php` | Branding defaults and `.env` mapping |
| `config/installer.php` | Test enforcement flag |
| `tests/Feature/AppInstallerTest.php` | Feature tests |
| `tests/Feature/InstallationIntegrationTest.php` | Full HTTP install flow from empty database |

## Related docs

- [Getting started](01-getting-started.md)
- [Testing](15-testing.md)
