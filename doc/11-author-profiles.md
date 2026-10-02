# Author Profiles

## Author aliases

One login can own multiple public author identities (aliases). Manage them at **Profile → Author aliases**: create, switch the active alias for writing, or delete.

- Each alias has its own `@username`, name, and bio
- Posts are tied to the alias used when writing
- Deleting an alias soft-removes its profile (empty public page) but **posts stay at their URLs** with “Unavailable author”

**Key files:** `AuthorAlias` model, `AuthorAliasController`, `ActiveAuthorAlias`, `profile/aliases.blade.php`

## Overview

Public author pages show a writer's bio, stats, and published stories at a clean URL.

| | |
|---|---|
| **Route** | `GET /@{username}` |
| **Who** | Everyone |

Example: `/@demoauthor`

## Authors directory

`GET /authors` lists public author aliases. **Super Admin** accounts are never listed. Admins can mark a user as **system**; system aliases are hidden from `/authors` and search until they have at least one published post. Direct profile URLs (`/@username`) still work.

**Key files:** `AuthorController`, `AuthorAlias::scopeListedInDirectory()`

## Profile header

Displays:

- Avatar and display name
- Username (`@handle`)
- Bio (if set)
- Follower count and following count
- **Follow** button (verified users)
- **Subscribe** button for email updates (verified users)

## Story list

Paginated list of the author's **published** posts, including **approved** magazine stories. Pending or rejected magazine submissions are hidden here (the author still sees them under `/me/posts`). **Pinned** posts always appear at the top (ordered by pin date, then publish date). Drafts and unlisted posts are not shown on the public profile (author sees them under `/me/posts`).

## Pin a post

Authors can pin published stories to the top of their public profile.

| | |
|---|---|
| **Who** | Post owner |
| **Where** | `/me/posts` — **Pin** / **Unpin** button |
| **Route** | `POST /write/{post}/pin` (`posts.pin`) |

- Only **published** posts can be pinned.
- Unpublishing or moving to draft automatically unpins.
- Pinned posts show a **Pinned** badge on the profile and post cards.

**Key files:** `PostController::togglePin`, `PostPolicy::pin`, `Post::isPinned()`

## Followers

Follower count updates when users follow or unfollow via `POST /@{username}/follow`.

**Key files:** `AuthorController`, `resources/views/authors/show.blade.php`

## Author subdomain pages (optional)

When the **`author_subdomains`** feature is enabled, each author is also available at `{username}.{base-host}` (e.g. `http://jane.example.com/`). Posts open at `{username}.{base-host}/{slug}` without the `@` prefix.

Local Docker tip: use base host **`localhost`** and test in **Chrome** — `http://techwriter.localhost:8888/`. **Safari on macOS** often cannot resolve `*.localhost` (Chrome works); see [Browser support](24-site-settings.md#browser-support-localhost-local-dev-only) in site settings doc.

```bash
php artisan site:setting set author_subdomains on
php artisan site:setting set author_subdomain_base_host localhost
# → http://techwriter.localhost:8888/
```

**Key files:** `App\Support\AuthorSubdomain`, `ServeAuthorSubdomain` middleware

## SEO

Author pages include meta tags for name, bio, and avatar.

## Block and report

From a profile or post context, verified users can block or report the author. See [Social interactions](07-social-interactions.md).

## Related docs

- [Social interactions](07-social-interactions.md)
- [User profile](12-user-profile.md) — editing your own profile
- [Site settings & CLI](24-site-settings.md) — subdomain URLs and `site:setting`
