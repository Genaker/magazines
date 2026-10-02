# Site settings & CLI

Site-wide configuration (name, features, locales, media, author subdomains) is stored in the `site_settings` table. Super admins can change most values at **`/admin/settings`**, or use the **`site:setting`** Artisan command (no browser login required).

## CLI: `site:setting`

| Action | Command |
|--------|---------|
| List features + stored settings | `php artisan site:setting list` |
| Read one key | `php artisan site:setting get {key}` |
| Write | `php artisan site:setting set {key} {value}` |
| Remove override (features fall back to config default) | `php artisan site:setting unset {key}` |

Docker:

```bash
docker exec medium-clone_app_1 php artisan site:setting list
```

### Feature flags

Feature names match `config/features.php` (no `feature_` prefix needed):

```bash
php artisan site:setting set author_subdomains on
php artisan site:setting set magazines off
php artisan site:setting get registration
php artisan site:setting unset author_subdomains   # back to config default
```

Booleans accept: `on` / `off`, `true` / `false`, `1` / `0`, `yes` / `no`.

Stored as `feature_{name}` in `site_settings` (e.g. `feature_author_subdomains`).

### Common keys

| Key | Example | Notes |
|-----|---------|--------|
| `site_name` | `My Blog` | Nav title, PWA |
| `site_tagline` | `Stories for everyone` | |
| `home_layout` | `discover` | `discover`, `latest`, or `trending` |
| `admin_path` | `desk` | Admin URL prefix (default `admin`). Also set `ADMIN_PATH` in `.env`. **Restart PHP** after changing. |
| `author_subdomains` | `on` | Feature flag (see below) |
| `author_subdomain_base_host` | `localhost` | Hostname for `{username}.{host}` (local dev) |
| `author_subdomain_redirect` | `on` | 301 from `/@username` to subdomain |
| `magazine_subdomains` | `on` | Feature flag for magazine subdomains |
| `magazine_subdomain_redirect` | `on` | 301 from `/magazine/{slug}` to subdomain |
| `subdomain_label_separator` | `-` | Spaces/underscores → this character; duplicate separators collapse |

Subdomain host labels (`{username}` or `{magazine-slug}`) are normalized via `App\Support\SubdomainLabel`: spaces and `_` become the separator (default `-`), repeated separators collapse (`foo - bar` → `foo-bar`), edges trim. Default in `config/subdomain.php`; override with `subdomain_label_separator` (single `-` recommended for DNS).

Subdomain helpers (`AuthorSubdomain::setBaseHost`, `setRedirect`) are used automatically for the author/magazine keys above.

**Key files:** `App\Console\Commands\SiteSettingCommand`, `App\Support\Features`, `App\Models\SiteSetting`

---

## Author subdomain pages

Optional feature: each author is also served at **`{username}.{base-host}`** with posts at **`{username}.{base-host}/{slug}`**.

| | |
|---|---|
| **Feature flag** | `author_subdomains` |
| **Path URLs (always)** | `GET /@{username}`, `GET /@{username}/{slug}` |
| **Subdomain URLs (when enabled)** | `GET http://jane.example.com/`, `GET http://jane.example.com/my-post` |

Enable:

```bash
php artisan site:setting set author_subdomains on
php artisan site:setting set author_subdomain_base_host localhost   # local Chrome / curl
php artisan site:setting set author_subdomain_redirect on           # optional 301 from /@username
```

**Key files:** `App\Support\AuthorSubdomain`, `ServeAuthorSubdomain` middleware, `RedirectToAuthorSubdomain` middleware, `config/author-subdomain.php` (reserved names)

### Magazine subdomain pages

Same base host as author subdomains (`author_subdomain_base_host` / `APP_URL`). Each magazine at **`{slug}.{base-host}`**; approved magazine posts at **`{slug}.{base-host}/{post-slug}`** (not on the author subdomain).

| | |
|---|---|
| **Feature flag** | `magazine_subdomains` |
| **Path URLs (always)** | `GET /magazine/{slug}` |
| **Subdomain URLs (when enabled)** | `GET http://the-commons.example.com/`, `GET http://the-commons.example.com/my-post` |

```bash
php artisan site:setting set magazine_subdomains on
php artisan site:setting set author_subdomain_base_host localhost
php artisan site:setting set magazine_subdomain_redirect on
```

If a hostname matches both a magazine slug and an author username, the **magazine** wins.

**Key files:** `App\Support\MagazineSubdomain`, `ServeMagazineSubdomain` middleware, `App\Support\PostUrl`

---

## Domain setup: why not `localhost:8888`?

Three different things often get mixed up:

| Concept | Example | Role |
|---------|---------|------|
| **Main site URL** | `http://127.0.0.1:8888` or `http://localhost:8888` | Home feed, `APP_URL` |
| **Admin URL** | `http://lvh.me:8888/admin/login` (Docker) | Platform admin — separate sign-in from site `/login` |
| **Port** | `:8888` | Docker nginx maps host `8888` → container `80` — same for all hostnames |
| **Author subdomain hostname** | `http://techwriter.localhost:8888` | **Different host**, same port and server |

Author subdomains are **not** paths on the main site. They are separate hostnames:

```
Main site:     http://localhost:8888/              ← one hostname
Author page:   http://techwriter.localhost:8888/    ← another hostname, same port
```

So **`localhost:8888` alone is correct for the main app**, but it cannot serve `techwriter` as a subdomain — you need `techwriter.{baseHost}:8888`.

### Base host

Derived in this order:

1. Admin / CLI **`author_subdomain_base_host`** (if set)
2. Hostname from **`APP_URL`** (e.g. `127.0.0.1` from `http://127.0.0.1:8888`)
3. Current request host (fallback)

```bash
php artisan site:setting get author_subdomain_base_host
```

Set explicitly when `APP_URL` uses `127.0.0.1` but you want author URLs on `*.localhost`:

```bash
php artisan site:setting set author_subdomain_base_host localhost
```

For **Safari** (which often cannot resolve `*.localhost`), use `lvh.me` instead — see [Browser support: `*.localhost`](#browser-support-localhost-local-dev-only) below.

### Browser support: `*.localhost` (local dev only)

This is **not an app bug**. Docker, nginx, and Laravel serve author subdomains correctly. The failure happens when the **browser or OS cannot resolve** the hostname `techwriter.localhost` to `127.0.0.1`.

RFC 6761 reserves `.localhost` for loopback, but each browser implements that differently. On **macOS** in practice:

| Client | `http://techwriter.localhost:8888` | Notes |
|--------|-------------------------------------|--------|
| **Google Chrome** | Works | Resolves `*.localhost` → `127.0.0.1` — **default for local testing** |
| **curl** | Works | macOS resolver often treats `*.localhost` as loopback |
| **Safari** | Often fails | Error: *“Safari can't find the server techwriter.localhost”* |
| **ping / `host`** | Often fails | Standard DNS tools return NXDOMAIN; unrelated to whether Chrome works |

**Symptoms in Safari:** opening `http://techwriter.localhost:8888` shows “Can't Find the Server”, while the same URL works in Chrome. Redirects from `http://127.0.0.1:8888/@techwriter` also break in Safari if they target `*.localhost`.

**This affects local dev only.** Production uses a real domain (`*.example.com`) with normal DNS — all browsers work.

#### Workarounds

| Goal | What to do |
|------|------------|
| Test in **Chrome** | Base host `localhost` (default) — no extra setup |
| Test in **Safari** | Switch base host to `lvh.me`: `php artisan site:setting set author_subdomain_base_host lvh.me` → `http://techwriter.lvh.me:8888/` |
| **Safari** + keep `localhost` | Add to `/etc/hosts`: `127.0.0.1 techwriter.localhost` (one line per author) |
| Avoid subdomains locally | Turn off redirect and use path URLs only: `http://localhost:8888/@techwriter` |

#### Quick check from terminal

```bash
# App responds (server OK)
curl -sI http://techwriter.localhost:8888/

# DNS may still fail in system tools — that is expected on macOS
host techwriter.localhost   # often NXDOMAIN even when Chrome works
```

### Local dev: base host comparison

| Base host | Author URL | Chrome | Safari |
|-----------|------------|--------|--------|
| `127.0.0.1` | `http://techwriter.127.0.0.1:8888` | No | No — IP subdomains are invalid |
| **`localhost`** | `http://techwriter.localhost:8888` | **Yes** (recommended for local test) | Often no — see [browser table](#browser-support-localhost-local-dev-only) |
| `lvh.me` | `http://techwriter.lvh.me:8888` | Yes | Yes — use when Safari must work |
| `/etc/hosts` | `127.0.0.1 techwriter.localhost` | Yes | Yes — one line per author |

### Recommended local setup (Docker + Chrome)

1. Main site: **http://localhost:8888** or **http://127.0.0.1:8888** (`APP_PORT=8888`).
2. Enable subdomains with base host `localhost`:

```bash
docker exec medium-clone_app_1 php artisan site:setting set author_subdomains on
docker exec medium-clone_app_1 php artisan site:setting set author_subdomain_base_host localhost
```

3. Open in **Google Chrome**:

| Page | URL |
|------|-----|
| Main site | http://localhost:8888 |
| Tech Writer profile | http://techwriter.localhost:8888/ |
| Sample post | http://techwriter.localhost:8888/getting-started-with-laravel |
| Redirect test | http://127.0.0.1:8888/@techwriter → `301` → `techwriter.localhost:8888` |

Use the **`localhost` hostname** (not bare `:8888`) for author subdomains — Chrome resolves `*.localhost` to `127.0.0.1`.

Bundled nginx accepts `localhost`, `*.localhost`, `lvh.me`, and `*.lvh.me` (`docker/nginx/default.conf`).

### Production

1. DNS wildcard: `*.example.com` → your server (same as `example.com`).
2. TLS wildcard cert or automated certs per host.
3. Web server `server_name example.com *.example.com;`
4. `APP_URL=https://example.com`
5. Leave **base host** empty to use `APP_URL`, or set `author_subdomain_base_host` to `example.com`.

Reserved subdomain labels (never map to authors): `www`, `admin`, `api`, `app`, … — see `config/author-subdomain.php`.

### Sessions across subdomains

The main site and magazine/author hosts (e.g. `http://lvh.me:8888` and `http://the-commons.lvh.me:8888`) are **different hostnames**. You need a shared session cookie domain so login persists on both.

#### Why `*.localhost` cannot share login cookies

`localhost` is on the browser **public suffix list**. Browsers **reject** cookies with `Domain=.localhost`, so `SESSION_DOMAIN=.localhost` has no effect — you will always appear logged out on `the-commons.localhost:8888` even after signing in on `localhost:8888`.

**Use `lvh.me` for local subdomain dev with shared login** (Docker default). All `*.lvh.me` names resolve to `127.0.0.1`, and `Domain=.lvh.me` cookies work in Chrome, Firefox, and Safari.

| Main site | Magazine example | Shared login |
|-----------|------------------|--------------|
| `http://lvh.me:8888` | `http://the-commons.lvh.me:8888` | Yes (`SESSION_DOMAIN=.lvh.me`) |
| `http://localhost:8888` | `http://the-commons.localhost:8888` | **No** (PSL blocks cookie domain) |

#### Automatic setup (Docker)

Docker Compose defaults:

```env
APP_URL=http://lvh.me:8888
SESSION_DOMAIN=.lvh.me
```

On container start, if `author_subdomain_base_host` is empty or `localhost`, it is set to `lvh.me` automatically.

Restart after changes, then **sign in again** on `http://lvh.me:8888` so the browser gets a new cookie.

#### Manual `.env`

| Base host | Main site URL | `SESSION_DOMAIN` |
|-----------|---------------|------------------|
| `lvh.me` (recommended local) | `http://lvh.me:8888` | `.lvh.me` |
| `example.com` (production) | `https://example.com` | `.example.com` |
| `localhost` | path URLs only, or accept no shared login | *(leave unset)* |

Do **not** use `SESSION_DOMAIN=.localhost` — browsers ignore it. Do **not** set a cookie domain when the base host is an IP (`127.0.0.1`).

```bash
./scripts/docker-artisan.sh site:setting set author_subdomain_base_host lvh.me
```

---

### Troubleshooting: logged out on Profile or magazine subdomain

**Symptoms**

- Signed in on the main site, but `http://the-commons.localhost:8888/` (or another subdomain) shows **Login**.
- Same after visiting `/profile` or a magazine from the nav.

**Cause**

Either (1) you are on `*.localhost` and browsers cannot share session cookies across those hosts, or (2) `SESSION_DOMAIN` / base host / login URL do not match (e.g. sign-in on `127.0.0.1` but magazine on `lvh.me`).

**Fix (Docker — recommended)**

1. Restart so `.env` picks up Docker env:

   ```bash
   docker compose up -d
   ```

2. Set base host to `lvh.me` (if still `localhost`):

   ```bash
   ./scripts/docker-artisan.sh site:setting set author_subdomain_base_host lvh.me
   ```

3. Confirm env (in container):

   ```bash
   docker exec medium-clone_app_1 grep -E '^(APP_URL|SESSION_DOMAIN)=' .env
   ```

   Expect `APP_URL=http://lvh.me:8888` and `SESSION_DOMAIN=.lvh.me`.

4. **Sign out**, then sign in on **`http://lvh.me:8888`** (not `localhost` or `127.0.0.1`).

5. Open the magazine on **`http://the-commons.lvh.me:8888/`** — you should stay logged in.

6. Profile: **`http://lvh.me:8888/profile`**

**Verify cookie (Chrome DevTools → Application → Cookies)**

After login on `lvh.me`, the session cookie should show `Domain: .lvh.me`.

**If you must keep `*.localhost` URLs**

Shared login is not possible. Either:

- Turn off subdomain redirects and use path URLs only (`/magazine/the-commons`, `/@username`), or
- Accept signing in separately on each hostname (not supported by design).

**Still stuck?**

| Check | Action |
|-------|--------|
| Logged in on `127.0.0.1:8888` | Use `http://lvh.me:8888` for login — cookies do not cross `127.0.0.1` ↔ `lvh.me` |
| Old session cookie | Clear site cookies for `lvh.me` / `localhost`, sign in again on `lvh.me` |
| Passwordless account | Use magic link on `lvh.me`, or set password under Profile |
| Need admin access | Sign in at **`/admin/login`** — site `/login` does not grant admin access (separate session cookie) |
| Safari | `lvh.me` works in Safari; `*.localhost` often does not resolve — see [Browser support](#browser-support-localhost-local-dev-only) |

**Key files:** `App\Support\SubdomainSession`, `ConfigureSubdomainSession` middleware, `App\Support\SiteUrl`

---

## Related docs

- [Admin panel](13-admin.md) — `/admin/settings` UI
- [Author profiles](11-author-profiles.md) — `/@username` paths
- [Extensibility](20-extensibility.md) — feature flag definitions in `config/features.php`
- [Getting started](01-getting-started.md) — Docker ports and `APP_URL`
