# Magazines — Feature Documentation

**Magazines** is a publishing platform for local communities, publishers, and bloggers. It connects readers and writers through publications, categories, and social features — with **integrated AI writing and grammar assistants** on the editor.

Documentation for every major feature in this project. Each page covers what the feature does, who can use it, routes, and key code locations.

## Contents

| Doc | Topic |
|-----|--------|
| [Getting started](01-getting-started.md) | Local dev, Docker, stack, seeded accounts |
| [Authentication](02-authentication.md) | Register, magic link, password login, admin login, verification |
| [Posts & authoring](03-posts-and-authoring.md) | Write, edit, publish, draft, unlisted, editor |
| [Feed & discovery](04-feed-and-discovery.md) | Home page, personalized feed |
| [Search](05-search.md) | Search posts and authors |
| [Categories & tags](06-categories-and-tags.md) | Hierarchy, follow, category requests |
| [Social interactions](07-social-interactions.md) | Follow, like, comment, block, report |
| [Reading lists](08-reading-lists.md) | Save stories to multiple lists |
| [Notifications](09-notifications.md) | Activity bell and notification types |
| [Magazines](10-magazines.md) | Publications, submissions, join requests |
| [Author profiles](11-author-profiles.md) | Public author pages |
| [User profile](12-user-profile.md) | Account settings, blocked users |
| [Admin panel](13-admin.md) | Moderation, users, categories, trash, admin sign-in |
| [Architecture](14-architecture.md) | Models, services, policies, caching |
| [Testing](15-testing.md) | PHPUnit and Playwright e2e |
| [Localization](16-localization.md) | English + Ukrainian, locale switcher |
| [PWA](17-pwa.md) | Installable web app, manifest, service worker |
| [Styling](18-styling.md) | Tailwind CSS v4, SCSS, Vite build |
| [Static assets](19-static-assets.md) | CSS/JS build, cache busting, version bump |
| [Extensibility](20-extensibility.md) | Domain events, listeners, feature flags |
| [Scheduler](21-scheduler.md) | Config-driven cron, single `schedule:run` entry |
| [Web installer](22-installer.md) | First-run setup wizard at `/install` |
| [HTTP API](23-api.md) | Sanctum auth, `POST /api/v1/posts` |
| [Site settings & CLI](24-site-settings.md) | `site:setting` command, author subdomains, local domain setup |

## Roles

| Role | Access |
|------|--------|
| Guest | Read published/unlisted posts, search, like (by IP) |
| User (`auth` + `verified`) | Write, comment, follow, lists, magazines |
| Admin | `/admin` dashboard, users, posts, reports |
| Super admin | Site settings, trash, delete users, tenant management (when multi-tenancy is on) |

## Default test accounts

| Email | Password | Role | Admin sign-in (Docker) |
|-------|----------|------|------------------------|
| `admin@magazines.test` | `password` | super_admin | `http://lvh.me:8888/admin/login` (or `/admin/login/password`) |
| `author@magazines.test` | `password` | user (owns seeded magazine **The Commons** at `/magazine/the-commons`) | Site `/login` only |
