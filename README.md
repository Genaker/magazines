# Magazines

**Open publishing for a free society.**

Magazines is a self-hosted publishing platform for independent writers, local communities, and small publishers. It gives people a place to publish ideas, report news, and build readership **without relying on closed, algorithm-driven silos** that decide what the public is allowed to see.

In a free society, speech needs **infrastructure**: readable pages, discoverable feeds, accountable moderation, and tools that help ordinary people write well. Magazines is that infrastructure — yours to run, configure, and govern.

---

## Why this platform

| Principle | What Magazines provides |
|-----------|-------------------------|
| **Open discourse** | Public posts, author pages, categories, tags, and search — stories are findable, not buried by opaque ranking |
| **Independent voices** | Anyone can register (when enabled), write, and build an audience; magazines let groups run their own publications |
| **Local & community focus** | Category navigation, follows, and personalized feeds connect readers to writers they choose |
| **Accountability** | User reports, admin review, bans, soft-delete/trash — moderation without pretending harm does not exist |
| **Transparency of control** | Super admins toggle features (registration, comments, AI tools, etc.) from `/admin/settings` — no hidden product decisions |
| **Writer empowerment** | TinyMCE editor, autosave, drafts, unlisted links, cover images, and optional AI writing assistants on the write page |

Magazines is **not** a replacement for the law, journalism ethics, or community norms. It is a **tool** for communities that want publishing they own.

---

## Features

### Reading & discovery

- **Home feed** — top stories this week, trending last hour, trending today (rolling 24h), and latest
- **Personalized feed** — for logged-in users who follow authors, categories, or tags
- **Author profiles** — `/@username` with posts, bio, followers, **pinned posts** at the top
- **Categories & tags** — hierarchical categories, `#tag` pages, follow category/tag
- **Search** — posts and authors
- **Post pages** — likes, views, reading time, cover images, responsive gallery

### Writing & publishing

- **Rich editor** — TinyMCE with autosave and revision conflict detection
- **Post statuses** — draft, published, unlisted (shareable link)
- **Tags** — up to 10 per post
- **Cover images** — responsive variants (sm / md / lg)
- **AI writing tools** — quick links to ChatGPT, Claude, Gemini, Grok, etc. with a copyable prompt (feature toggle)

### Magazines (publications)

- Create and run **magazine** communities
- Member roles, submission queue, join requests
- Magazine logos and dedicated pages

### Social & engagement

- **Follow** authors, categories, and tags
- **Likes** — authenticated users and guests (by IP)
- **Comments** — built-in threads, or **Disqus** (super-admin setting)
- **Pin posts** — authors pin published stories to the top of their profile (`/me/posts`)
- **Reading lists** — save posts to multiple lists
- **Notifications** — likes, comments, follows, magazine activity
- **Author subscribe** — email on new stories (instant or daily digest via cron)
- **Block & report** users

### Authentication

- Registration (toggle) — CTA on login and magic-link pages
- **Magic link** login — email link + 6-digit code (toggle)
- Password login
- Email verification required for writing and social actions

### Administration

- **Admin dashboard** — users, posts, categories, reports, category requests
- **Data grids** — search, sort, pagination on admin lists
- **Soft delete & trash** — recover users/posts (super admin)
- **Site settings** — site name, tagline, feature toggles, Disqus shortname
- **Ban users** — with forced logout

### Localization

- **English** (default) and **Ukrainian** (`ua`)
- Laravel `lang/` files + JSON translations
- **EN | UK** switcher in navigation (session-based)
- See [doc/16-localization.md](doc/16-localization.md)

### PWA (installable app)

- Web app manifest at `/site.webmanifest`
- Service worker for install prompts
- iOS **Add to Home Screen** support (Safari + HTTPS)
- See [doc/17-pwa.md](doc/17-pwa.md)

### Site branding

- Custom magazine logo and favicon (`public/images/`)
- Full-width footer: *Platform for a free society* + © 2014–now

### Technical

- Laravel 13, Blade, Tailwind CSS v4, SCSS, Vite, Alpine.js
- MariaDB + Redis (sessions, cache, entity cache)
- CSRF protection on mutating requests
- PHPUnit + Playwright e2e tests
- Docker Compose stack (Nginx, PHP-FPM, MariaDB, Redis, Mailpit)

**Detailed feature docs:** [doc/README.md](doc/README.md) — including [styling (Tailwind + SCSS)](doc/18-styling.md)

---

## Quick start (Docker)

Recommended for a production-like setup on your machine.

### Prerequisites

- Docker Desktop (or Docker Engine + Compose)
- Git

### Install & run

```bash
git clone <your-repo-url> magazines
cd magazines

composer install
npm ci
npm run build

docker-compose up -d --build
```

Open **http://127.0.0.1:8888**

| Service | URL |
|---------|-----|
| **App** | http://127.0.0.1:8888 |
| **Mailpit** (email inbox) | http://127.0.0.1:8025 |
| **MariaDB** | `127.0.0.1:3307` — user `root`, password `secret`, database `magazines` |
| **Redis** | `127.0.0.1:6380` |

On first boot the entrypoint creates `.env` if missing, runs migrations, and seeds demo data when the database is empty.

**One Mailpit only** — use the Docker service on port **8025**. Do not run a separate `mailpit` on the host at the same time.

If the database is corrupted or from an old project name:

```bash
docker-compose down -v
docker-compose up -d --build
```

---

## Local development (without Docker)

### Prerequisites

- PHP **8.4+** (`pdo_mysql`, `redis`, `mbstring`, `xml`, `curl`, `fileinfo`)
- Composer, Node.js **20+**
- MariaDB or MySQL — `CREATE DATABASE magazines;`
- Redis on `127.0.0.1:6379`
- Mailpit on `127.0.0.1:1025` — `brew install mailpit && mailpit`

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set in `.env` (hostnames must be `127.0.0.1`, not Docker service names):

```
APP_URL=http://127.0.0.1:8000

DB_HOST=127.0.0.1
DB_DATABASE=magazines

SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAILPIT_WEB_URL=http://127.0.0.1:8025
```

Then:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
npm ci
npm run build
```

### Run

```bash
composer dev
```

| Service | URL |
|---------|-----|
| App | http://127.0.0.1:8000 |
| Mailpit | http://127.0.0.1:8025 |

Or separately: `php artisan serve`, `npm run dev`, and `mailpit`.

---

## Default accounts

| Email | Password | Role |
|-------|----------|------|
| `admin@magazines.test` | `password` | super_admin |
| `author@magazines.test` | `password` | user (owns **The Commons** at `/magazine/the-commons`) |

Super admin: **Admin → Settings** at http://127.0.0.1:8888/admin/settings (Docker) to enable/disable features.

---

## Tests

See **[doc/15-testing.md](doc/15-testing.md)** for full details (test vs dev Docker stacks, troubleshooting).

```bash
composer test              # PHPUnit in Docker (magazines_test DB)
composer test:single-tenant
sh scripts/test-auth.sh    # Auth / registration PIN flow only
npm run test:e2e           # Playwright + MariaDB (docker/e2e.sh)
sh scripts/test-auth-e2e.sh
composer analyse           # PHPStan static analysis (app/)
```

Use `composer test` — not bare `php artisan test` on the host unless MariaDB on `127.0.0.1:3307` is reachable and `magazines_test` exists.

**Manual registration test:** http://127.0.0.1:8888/register → PIN on `/login` → see [Authentication](doc/02-authentication.md#manual-test-local-docker). If the site hangs: `./scripts/docker-restart.sh`.

---

## Key routes

| Route | Description |
|-------|-------------|
| `/` | Home — trending, latest, personalized sections |
| `/@{username}` | Author profile |
| `/@{username}/{slug}` | Post |
| `/login` | Magic link sign-in |
| `/register` | Create account (if enabled) |
| `/posts/create` | Write (auth + verified) |
| `/magazines` | Publications |
| `/search` | Search |
| `/admin` | Admin panel |
| `/admin/settings` | Site settings (super admin) |
| `/locale/{en\|ua}` | Switch language |
| `/site.webmanifest` | PWA manifest |
| `/me/posts` | My stories (pin/unpin) |

---

## Localization

Default language is English. Users switch to Ukrainian via **EN | UK** in the header.

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

Translation files live in `lang/en/` and `lang/ua/`. Details: [doc/16-localization.md](doc/16-localization.md).

## Install as an app (PWA)

On iPhone: Safari → Share → **Add to Home Screen**. Requires HTTPS in production. Details: [doc/17-pwa.md](doc/17-pwa.md).

---

## Self-hosting notes

- Change default passwords and `APP_KEY` before going live.
- Set `APP_DEBUG=false` and `APP_ENV=production` in production.
- Put Nginx or Caddy in front of the app with TLS.
- Back up MariaDB and `storage/app` regularly.
- Review feature toggles and moderation workflow for your community’s standards.
- **Scheduler:** add one crontab line — `* * * * * php artisan schedule:run` — or run the Docker `scheduler` service. See [doc/21-scheduler.md](doc/21-scheduler.md).

---

## License

See repository license file. Use and modify for your community’s publishing needs.
