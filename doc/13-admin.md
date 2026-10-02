# Admin Panel

## Overview

Staff manage users, content, and moderation from a separate admin area.

| | |
|---|---|
| **Base URL** | `/admin` |
| **Sign in** | `/admin/login` — separate from public `/login` (see below) |
| **Who** | `admin` or `super_admin` role |
| **Navigation** | Dark admin bar on every `/admin/*` page (Users, Posts, …). Logged-in admins also see an **Admin** section in the profile sidebar with Dashboard and Users shortcuts. |

Super admins have additional access to site settings and permanent deletion from trash.

### Custom admin URL (optional)

For security, change the `/admin` prefix via **`ADMIN_PATH`** in `.env` or **`php artisan site:setting set admin_path your-prefix`**. The session cookie path follows the new prefix. **Restart PHP** (and run `php artisan route:clear` if routes are cached) after changing. See [Site settings & CLI](24-site-settings.md).

**Key file:** `App\Support\AdminPath`, `routes/admin.php`

## Sign in

Admin authentication is **isolated** from the public site:

| | Site (`/login`) | Admin (`/admin/login`) |
|---|---|---|
| Session cookie | Site session | `*-admin-session` (path `/admin`) |
| Grants reader access | Yes | No |
| Grants admin access | No | Yes (admin/super_admin only) |

Signing in on the homepage does not open `/admin`. Signing in at `/admin/login` does not keep you logged in on the reader site.

### Magic link (when feature enabled)

Same flow as [site magic link login](02-authentication.md#magic-link-default): enter email → receive link + 6-digit code → enter code or click link.

| | |
|---|---|
| **Routes** | `GET /admin/login`, `POST /admin/login/magic-link`, `GET /admin/login/magic-link/verify`, `POST /admin/login/magic-link/code` |
| **Local URL** | `http://lvh.me:8888/admin/login` (Docker) |

Only **admin** and **super_admin** accounts receive an email. Other addresses still see a generic “link sent” message (no enumeration). Successful sign-in redirects to `/admin`.

In local debug mode (`APP_DEBUG=true`), the code is shown on the page like `/login`.

### Password login

| | |
|---|---|
| **Route** | `GET /admin/login/password`, `POST /admin/login` |
| **Link** | “Use password instead” on the admin magic-link page |

When **Magic link login** is disabled in site settings, `/admin/login` shows the password form directly.

**Key files:** `AdminAuthenticatedSessionController`, `MagicLinkController` (`storeAdmin`, `verifyAdmin`, `verifyCodeAdmin`), `App\Support\AdminSession`, `ConfigureAdminSession` middleware, `resources/views/auth/admin-login.blade.php`, `resources/views/auth/admin-login-password.blade.php`

## Dashboard

`GET /admin` — summary stats: users, posts, pending reports, category requests.

**Key files:** `Admin\DashboardController`, `resources/views/admin/layout.blade.php`

## Users

| Action | Purpose |
|--------|---------|
| List users | Search and filter |
| Edit user | Change role, ban/unban, mark as system user |
| System user | Hidden from `/authors` when the alias has no published posts; Super Admin is always hidden |
| Ban | Sets `banned_at`; user is logged out on next request |
| Soft delete | Moves user to trash |

**Key files:** `Admin\UserController`, `EnsureUserIsNotBanned` middleware

## Posts

| Action | Purpose |
|--------|---------|
| List posts | All statuses |
| Edit / unpublish | Moderate content |
| Soft delete | Moves post to trash |

**Key files:** `Admin\PostController`

## User reports

Review reports submitted from author profiles.

| Route | `GET /admin/user-reports` |
| **Actions** | Dismiss, ban reported user |

**Key files:** `Admin\UserReportController`, `UserReport` model

## Category requests

Approve or reject user-submitted category proposals. Approval creates the category and notifies the requester.

**Key files:** `Admin\CategoryRequestController`

## Categories

CRUD for the category tree: create parents and subcategories, reorder, delete.

**Key files:** `Admin\CategoryController`

## Trash (super admin)

View and permanently delete soft-deleted users and posts.

**Key files:** `Admin\TrashController`

## Site settings (super admin)

| | |
|---|---|
| **Route** | `GET/PUT /admin/settings` |

Global configuration stored in `site_settings`:

- **Site name** and **tagline** (shown in nav and PWA manifest)
- **Home page layout** — discover (default), latest only, or trending only
- **Languages** — enable one or more (`en`, `ua`, …), set default; switcher shown only when multiple are enabled
- **Feature toggles** — open registration, registration invites, magic link, magazines, comments, reading lists, AI tools, etc.
- **Registration invites** (when invites feature is on) — create/revoke codes, set max uses and expiry, copy share links for closed communities
- **Comments** — optional **Disqus** embed instead of built-in comments (`comments_use_disqus`, `disqus_shortname`)
- **Images** — separate presets for **post** and **gallery** uploads:
  - Small / medium / large variant widths (px)
  - Max width and max height (default 1500×1500)
  - JPEG quality (default 85%)
  - **Do not resize** — store originals without generating variants

Settings apply to **new uploads only**; existing files are not reprocessed. Env defaults live in `config/media.php` (`MEDIA_POST_*`, `MEDIA_GALLERY_*`).

The same settings can be changed from the CLI — see [Site settings & CLI](24-site-settings.md):

```bash
php artisan site:setting list
php artisan site:setting set author_subdomains on
```

**Key files:** `Admin\SiteSettingController`, `SiteSetting` model, `App\Support\Features`, `App\Support\MediaSettings`, `App\Console\Commands\SiteSettingCommand`

## Roles

| Role | Access |
|------|--------|
| `user` | No admin access |
| `admin` | Dashboard, users, posts, reports, categories, category requests |
| `super_admin` | All of the above plus trash and site settings |

**Key files:** `User::isAdmin()`, `User::isSuperAdmin()`, admin middleware

## Related docs

- [Authentication](02-authentication.md)
- [Categories & tags](06-categories-and-tags.md)
- [Social interactions](07-social-interactions.md) — reports source
