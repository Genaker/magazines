# Testing

## Overview

The project has two test layers:

| Layer | Framework | Location |
|-------|-----------|----------|
| Static analysis | PHPStan + Larastan | `app/` |
| Feature / unit | PHPUnit | `tests/Feature/`, `tests/Unit/` |
| End-to-end | Playwright | `tests/e2e/` |

## PHPUnit

### Run

PHPUnit runs **in Docker** using the **same `app` image** as development (`Dockerfile` → `webdevops/php-dev:8.4`: FPM, GD, phpredis). **Do not run `php artisan test` on the host** against the dev MariaDB port (`3307`) — use the commands below so tests hit the isolated **test stack** (`magazines-test`, MariaDB host port **13307**, database `magazines_test`).

```bash
composer test
composer test:single-tenant   # default app mode — excludes tests/Feature/Tenancy/
composer test:multi-tenant    # domain tenants, session isolation, tenant admin
composer test:redis-search
composer test -- tests/Feature/PostTest.php

# Auth / registration (PIN verify + login flow)
sh scripts/test-auth.sh
composer test -- --filter='Registration|VerifiedLogin|PasswordlessRegistration|AuthFlowIntegration|RegistrationInvite|MagicLoginSecurity'
```

| PHPUnit suite | Location | When to run |
|---------------|----------|-------------|
| **SingleTenant** | `tests/Unit/`, `tests/Feature/` (except `Tenancy/`) | Day-to-day; multi-tenancy feature off |
| **MultiTenant** | `tests/Feature/Tenancy/` | After tenancy, domains, or tenant admin changes |
| **Feature** (full) | All of the above | CI / before release |

`docker/test.sh` runs `docker-compose run app` with testing env on **MariaDB** (`magazines_test`) by default. MariaDB and Redis Stack start automatically. If MariaDB is already healthy in the test stack, containers are **not recreated** (avoids slow Docker Desktop hangs).

### Dev and test stacks in parallel

PHPUnit and Playwright use a **separate Compose project** (`magazines-test`) with **different host ports** so `docker compose up` for development can stay running:

| Service | Dev (`magazines`) | Test (`magazines-test`) |
|---------|-------------------|-------------------------|
| App (nginx) | 8888 | 9888 |
| MariaDB | 3307 | 13307 |
| PostgreSQL | 5433 | 15433 |
| Redis Stack | 6380 | 16380 |
| Mailpit UI / SMTP | 8025 / 1025 | 18025 / 11025 |

Defaults are set in `docker/test-compose-env.sh` (sourced by `docker/test.sh` and `docker/e2e.sh`). Override any port via env when needed, e.g. `MARIADB_PORT=13308 composer test`.

Test volumes are named `magazines-test_*` (e.g. `magazines-test_postgres_data`). Recreate Postgres when changing images:

```bash
COMPOSE_PROJECT_NAME=magazines-test docker compose stop postgres
COMPOSE_PROJECT_NAME=magazines-test docker compose rm -f postgres
docker volume rm magazines-test_postgres_data
```

PostgreSQL tests build and use the **custom Postgres 16 image** (`docker/postgres/Dockerfile` — `pg_search` + **pgvector** on stock Debian bookworm). The service needs `seccomp:unconfined` on Docker Desktop.

Optional **ParadeDB** image for local dev: `POSTGRES_IMAGE=paradedb/paradedb:0.24.0-pg16 docker compose up -d postgres --no-build --pull always`

```bash
composer test:postgres
# or
TEST_DB=pgsql sh docker/test.sh
composer test:postgres -- --group postgres-search
```

Redis Stack starts automatically for RediSearch tests (`seccomp=unconfined` on the redis service).

## PHPStan (static analysis)

PHPStan is installed as a **project dev dependency** (`phpstan/phpstan`, `larastan/larastan`). Config: `phpstan.neon.dist` (level 5, `app/` only). Known issues are recorded in `phpstan-baseline.neon` — fix code and shrink the baseline over time; do not add new baseline entries for fresh errors.

### Run

Uses the same Docker **app** image as dev and PHPUnit:

```bash
composer analyse
# or
sh docker/analyse.sh
# single path
composer analyse -- app/Services/Search
```

Host PHP (after `composer install`):

```bash
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=512M
```

Regenerate the baseline after fixing a batch of issues:

```bash
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --generate-baseline=phpstan-baseline.neon
```

### Coverage areas

| Area | Example test file |
|------|-------------------|
| Auth | `tests/Feature/Auth/` |
| Posts | `tests/Feature/PostTest.php`, `PostGalleryTest.php`, `PostVideoTest.php`, `MediaSettingsTest.php` |
| Video embeds | `tests/Unit/VideoEmbedTest.php` |
| Tag cloud | `tests/Feature/TagCloudTest.php` |
| Scheduler | `tests/Feature/ScheduleRegistrarTest.php` |
| Web installer | `tests/Feature/AppInstallerTest.php`, `tests/Feature/InstallationIntegrationTest.php` |
| CLI installer / users | `tests/Feature/Console/AppInstallCommandTest.php`, `CreateUserCommandTest.php`, `SeedDemoCommandTest.php` |
| HTTP API | `tests/Feature/Api/V1/` — see [23-api.md](23-api.md) |
| Reading lists | `tests/Feature/ReadingListTest.php` |
| Follow / like | `tests/Feature/` interaction tests |
| Admin | `tests/Feature/Admin/` |
| Policies | Authorization assertions in feature tests |

Feature tests hit HTTP endpoints and assert database state, redirects, and JSON responses.

### Integration tests (multi-step HTTP flows)

Located in `tests/Feature/Integration/`. Each file chains several subsystems in one scenario (unlike isolated feature tests).

| Flow | Test file | Covers |
|------|-----------|--------|
| Web install (empty DB) | `InstallationIntegrationTest.php` | Installer, migrations, branding, admin login |
| Register → publish | `Integration/AuthFlowIntegrationTest.php` | Register → PIN verifies + login → first post |
| Publish → discover | `Integration/PublishingFlowIntegrationTest.php` | Posts, search, tags, RSS, author page, home |
| Follow / like / comment | `Integration/SocialFlowIntegrationTest.php` | Social, notifications |
| API → web | `Integration/ApiPublishingIntegrationTest.php` | Sanctum, API v1, public post URL |
| API magazines & categories | `Integration/ApiContentIntegrationTest.php` | Sanctum, API v1 magazines, site/magazine categories, web pages |
| Magazine submissions | `Integration/MagazineFlowIntegrationTest.php` | Magazines, join, submit, approve |
| Report → ban | `Integration/ModerationFlowIntegrationTest.php` | User reports, admin ban |
| Reading lists | `Integration/ReadingListFlowIntegrationTest.php` | Bookmarks, custom lists |
| Category requests | `Integration/CategoryRequestFlowIntegrationTest.php` | Request, admin approve, post in category |
| Gallery & video posts | `Integration/GalleryVideoFlowIntegrationTest.php` | Publish gallery/video → public show |
| Scheduled publish | `Integration/ScheduledPublishFlowIntegrationTest.php` | Schedule, go live, feed, `publications:notify-due` |
| Magic link login | `Integration/MagicLinkFlowIntegrationTest.php` | Send link, code login, write page |
| Magic link security | `Integration/MagicLoginSecurityFlowIntegrationTest.php` | PIN verifies on login, lockout, CLI unlock |
| Passwordless registration | `Integration/PasswordlessRegistrationFlowIntegrationTest.php` | Register without password → PIN → set password |
| Admin login | `AdminLoginTest.php` | Separate admin session, password + magic link (single-tenant) |
| Multi-tenancy | `Tenancy/*.php` | Tenant domains, admin apex URL, session isolation — run `composer test:multi-tenant` |
| Author subscriptions | `Integration/AuthorSubscriptionFlowIntegrationTest.php` | Subscribe, publish, instant/digest email |
| Admin trash | `Integration/AdminTrashFlowIntegrationTest.php` | Soft-delete post, restore, public again |
| Admin URL prefix | `Integration/AdminPathIntegrationTest.php`, `Integration/AdminCustomPathIntegrationTest.php` | Default-path security notice; custom `ADMIN_PATH` login and dashboard |
| PWA manifest | `Integration/PwaManifestFlowIntegrationTest.php` | Branding in manifest + HTML link |
| Locale switching | `Integration/LocaleFlowIntegrationTest.php` | UA/EN home UI |
| AI writing tools | `Integration/AiWritingToolsFlowIntegrationTest.php` | Feature gate on write page |
| Static asset versioning | `Integration/StaticAssetVersionFlowIntegrationTest.php` | Bump command → versioned post.js URL |
| Password login | `Integration/PasswordLoginFlowIntegrationTest.php` | Login → publish → public URL |
| User block | `Integration/UserBlockFlowIntegrationTest.php` | Follow → block → post hidden |
| Unlisted posts | `Integration/UnlistedPostFlowIntegrationTest.php` | Link works, excluded from home/search |
| Author aliases | `Integration/AuthorAliasFlowIntegrationTest.php` | Pen name → publish under alias URL |
| Invite registration | `Integration/RegistrationInviteFlowIntegrationTest.php` | Closed signup with invite → PIN → publish |

Run integration tests:

```bash
composer test -- tests/Feature/Integration
composer test -- tests/Feature/InstallationIntegrationTest.php
sh scripts/test-auth.sh
```

### Manual browser test (registration PIN flow)

Use the **dev** Docker stack (port **8888**). If the site hangs, restart first — see [Getting started → Troubleshooting](01-getting-started.md#troubleshooting-site-cant-be-reached).

| Step | URL |
|------|-----|
| Register | http://127.0.0.1:8888/register |
| Enter PIN | http://127.0.0.1:8888/login (after register) |
| Email inbox (dev) | http://127.0.0.1:8025 |

Expected: register does **not** log you in; correct PIN verifies email **and** signs you in. No `/verify-email` step when magic link login is on.

Helper scripts (from project root):

| Script | Purpose |
|--------|---------|
| `sh scripts/test-auth.sh` | PHPUnit — auth / registration / PIN integration tests |
| `sh scripts/test-auth-e2e.sh` | Playwright — register + passwordless + magic-login security specs |
| `./scripts/docker-restart.sh` | Restart nginx + app when `:8888` hangs |

### Troubleshooting

| Symptom | Fix |
|---------|-----|
| `php artisan test` hangs with no output | Use `composer test` (Docker). Host PHPUnit uses dev MariaDB port **3307** and database `magazines_test` |
| Test stack MariaDB stopped (`Exit 0`) | `docker/test.sh` falls back to the **dev** stack (`medium-clone`, port 3307) when healthy. Or start test stack: `COMPOSE_PROJECT_NAME=magazines-test docker compose up -d mariadb redis` |
| Docker `Recreating mariadb` hangs | Scripts use `--no-recreate` and skip waits when MariaDB is already healthy |
| `MariaDB is not healthy` | `COMPOSE_PROJECT_NAME=magazines-test docker compose logs mariadb` (or `medium-clone` if using dev fallback) |
| Port clash with dev stack | Dedicated test stack uses **13307** (MariaDB), **9888** (app) — see table below |
| `docker compose run` times out | Raise timeout: `COMPOSE_HTTP_TIMEOUT=300 composer test`; ensure Docker Desktop has enough resources |
| Site unreachable at `:8888` | `./scripts/docker-restart.sh` — see [Getting started](01-getting-started.md#troubleshooting-site-cant-be-reached) |

## Playwright e2e

### Configuration

- `playwright.config.ts` — base URL, browsers, global setup
- `tests/e2e/global-setup.ts` — migrates MariaDB e2e DB, seeds data, starts app
- `tests/e2e/helpers/db-env.ts` — shared MySQL env (`magazines_e2e` on host port `13307`, test stack)

E2e uses **MariaDB** (`magazines_e2e` database). `docker/e2e.sh` starts MariaDB and creates the database before Playwright runs.

### Run

```bash
# First time
npx playwright install

# Run all e2e tests (starts MariaDB via Docker)
npm run test:e2e

# Auth / registration (PIN verify + login)
sh scripts/test-auth-e2e.sh
npm run test:e2e -- tests/e2e/auth/register-flow.spec.ts tests/e2e/auth/passwordless-registration.spec.ts tests/e2e/auth/magic-login-security.spec.ts

# Single-tenant only (excludes tests/e2e/tenants/ and admin/tenants.spec.ts)
npm run test:e2e:single-tenant

# Multi-tenancy e2e only
npm run test:e2e:multi-tenant

# Run one file
npm run test:e2e -- tests/e2e/posts/create-and-publish.spec.ts

# UI mode
npx playwright test --ui
```

### Test suites

| Directory | Topics |
|-----------|--------|
| `tests/e2e/auth/` | Login, register, magic link |
| `tests/e2e/posts/` | Create, publish, gallery post type, video post, AI tools, view count |
| `tests/e2e/reading-lists/` | Save to list, create list |
| `tests/e2e/feed/` | Home feed, tag cloud, personalized feed |
| `tests/e2e/search/` | Nav search |
| `tests/e2e/interactions/` | Like, comment, follow |
| `tests/e2e/moderation/` | Report post/user |
| `tests/e2e/magazines/` | Browse, create |
| `tests/e2e/profile/` | Blocked users, unblock |
| `tests/e2e/admin/` | Category requests, site settings, media/image settings, home layout |
| `tests/e2e/notifications/` | Bell, mark read |

### Seeded e2e users

Same as dev seeds: `author@magazines.test`, `admin@magazines.test` (password: `password`).

E2e admin helpers sign in via **`/admin/login/password`** (stable when magic link is the default on `/admin/login`).

Helpers in `tests/e2e/helpers/` provide login and navigation shortcuts.

### Recent feature coverage (e2e)

| Feature | Spec file |
|---------|-----------|
| Gallery post composer (captions, cover badge) | `tests/e2e/posts/gallery-post.spec.ts` |
| Video post (embed + description) | `tests/e2e/posts/video-post.spec.ts` |
| Tag cloud sidebar | `tests/e2e/feed/tag-cloud.spec.ts` |
| Admin image settings | `tests/e2e/admin/media-settings.spec.ts` |

Gallery publish flow is covered by `tests/Feature/PostGalleryTest.php` (multipart upload in Playwright is flaky in CI).

## CI considerations

- PHPStan: `composer analyse` (Docker)
- PHPUnit: `composer test` (Docker) for full coverage including RediSearch
- E2e: requires `npm ci`, Playwright browsers, and `php artisan serve` (handled by global setup)

## Related docs

- [Getting started](01-getting-started.md)
