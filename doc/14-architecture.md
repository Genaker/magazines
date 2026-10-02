# Architecture

## Application structure

```
app/
├── Http/Controllers/     # Web controllers (feature endpoints)
├── Http/Controllers/Admin/
├── Models/               # Eloquent models
├── Policies/             # Authorization (Post, ReadingList, etc.)
├── Services/             # Business logic (Feed, Autosave, Image, etc.)
├── Support/              # Helpers (Seo, etc.)
├── Jobs/                 # Async jobs (e.g. RecordPostView)
└── Notifications/        # Mail/notification classes
```

## Key services

| Service | Responsibility |
|---------|----------------|
| `FeedService` | Home feed query with follow-based personalization |
| `AutosaveService` | Draft snapshots and revision conflict detection |
| `ImageService` | Cover and avatar processing, responsive variants |
| `CategoryService` | Category tree, follow counts, caching |
| `ActivityNotifier` | Sends in-app `UserActivity` notifications |

## Authorization

Laravel policies gate model actions:

| Policy | Models / actions |
|--------|------------------|
| `PostPolicy` | view, update, delete by status and role |
| `ReadingListPolicy` | owner-only list management |
| `CommentPolicy` | delete own comment or admin |
| `MagazinePolicy` | owner/editor permissions |

Controllers call `$this->authorize()` or `Gate::authorize()` before mutating resources.

## Database

| Store | Use |
|-------|-----|
| MariaDB | Primary data (users, posts, lists, etc.; `DB_CONNECTION=mysql`) |
| Redis | Sessions (`SESSION_DRIVER=redis`), framework cache (`CACHE_STORE=redis`), entity query cache (`ENTITY_CACHE_STORE=redis`). Docker uses **Redis Stack** (RediSearch + vectors). Search indexes use connection `search` in `config/database.php` — same host by default, or `REDIS_SEARCH_*` for a dedicated server. |

PHPUnit and Playwright e2e use **MariaDB** (`magazines_test` / `magazines_e2e`) via Docker for isolated test runs.

## Logging & exceptions

| Layer | Behavior |
|-------|----------|
| Laravel default | Unhandled exceptions logged to `storage/logs/laravel.log` via `LOG_CHANNEL=stack` |
| `ExceptionLogger` | Structured logging for caught failures (`log($e, $message, $context)`) |
| Exception context | `LOG_EXCEPTION_CONTEXT=true` adds URL, method, IP, and `user_id` to every reported exception |
| Queue failures | `JobFailed` events logged with job name, queue, and connection |
| Manual logs | Magic link / verification email errors use `Log::error` in controllers |

Disable extra context with `LOG_EXCEPTION_CONTEXT=false`. Set `LOG_LEVEL=error` in production to reduce noise.

## Email

`QUEUE_CONNECTION=sync` — all mail sends inline in the request. No queue worker in dev or Docker.

Magic link and notification emails use Laravel's `Mail` facade and Mailable classes.

## Frontend assets

| Tool | Role |
|------|------|
| Vite | Bundles `resources/js/` and SCSS |
| Tailwind v4 + SCSS | See [Styling](18-styling.md) |
| Alpine.js | Lightweight interactivity in Blade |
| TinyMCE | Rich text editor on write pages |

Built assets live in `public/build/`. Post page interactions use `public/js/post.js`.

CSS and JS URLs include a `?v=` query from `static-asset-version`; `npm run build` bumps it after each Vite build. See [Static assets](19-static-assets.md) for the full architecture and commands.

## Routing

| File | Scope |
|------|-------|
| `routes/web.php` | Main app routes |
| `routes/auth.php` | Login, register, verification |
| `bootstrap/app.php` | Middleware registration |

## Soft deletes

`User` and `Post` use soft deletes. Admins recover or permanently purge from trash.

## Entity query cache

Hot Eloquent reads go through `App\Support\EntityCache` — Laravel's native `Cache::remember` pattern with tagged invalidation on model changes.

| Layer | Role |
|-------|------|
| `config/entity-cache.php` | Enable flag, Redis store, TTLs, tags per entity |
| `CachesQueries` trait | Model helpers (`rememberQuery`, `rememberQueryForever`) |
| Observers | Flush tags on create/update/delete (`CategoryObserver`, `PostObserver`, etc.) |

**Env vars:** `ENTITY_CACHE_ENABLED` (default `true`), `ENTITY_CACHE_STORE` (defaults to `CACHE_STORE`), per-entity TTLs (`ENTITY_CACHE_TTL_FEEDS`, etc.).

Set `ENTITY_CACHE_ENABLED=false` to bypass Redis and read straight from MariaDB — useful when debugging stale data locally.

**Cached today:** site settings, category navigation, post show pages, author profiles, feed section IDs (`FeedService`), tag/magazine lists.

**Live counters:** `views_count` and `likes_count` are stripped from cached `Post` payloads and re-loaded with one SQL query on read (`FreshPostCounts`). View recording no longer flushes feed cache.

## Performance notes

| Practice | Where |
|----------|--------|
| Cache IDs, eager-load relations | `FeedService::loadPostsByIds()` loads posts with `user`, `authorAlias`, `category`, `tags` |
| Async post views | Set `POST_VIEW_COUNT_MODE=async` + `QUEUE_CONNECTION=redis` and run a queue worker |
| Tagged invalidation | Observers flush entity tags so updates appear without waiting for TTL |
| Redis in Docker | `docker-compose.yml` wires Redis for sessions + cache out of the box |

PHPUnit uses `CACHE_STORE=array` and `ENTITY_CACHE_STORE=array` so tests stay isolated without Redis.

## Config highlights

| File | Purpose |
|------|---------|
| `config/entity-cache.php` | Redis entity query cache (enable, store, TTLs, tags) |
| `config/ai-writing-tools.php` | External AI assistant URLs and prompt templates |
| `config/media.php` | Upload limits, tag caps, post/gallery image sizes and JPEG quality |
| `config/site.php` | Site branding defaults (`SITE_*` env vars), footer, PWA colors, home layout |

## Docker

`docker-compose.yml` runs app (PHP), MariaDB, Redis, and Mailpit. Entrypoint waits for MariaDB then runs migrations.

**Key files:** `docker/entrypoint.sh`, `.env.docker.example`

## Related docs

- [Getting started](01-getting-started.md)
- [Testing](15-testing.md)
- [Styling](18-styling.md)
