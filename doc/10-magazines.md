# Magazines

## Overview

Magazines are publications (for local communities and publishers). Owners can invite editors, accept join requests, and receive story submissions from authors.

### Seeded example

After `php artisan db:seed`, the database includes **The Commons** (`/magazine/the-commons`), owned by `author@magazines.test`, with one approved story (*Why Writing Matters*).

| | |
|---|---|
| **Route** | `GET /magazines`, `GET /magazine/{slug}` |
| **Who** | Everyone can browse; verified users can create and submit |

## Create a magazine

| Route | `GET/POST /magazines/create` |
| **Who** | Verified users |

Fields include name, description, and optional cover image. The creator becomes the owner.

**Key files:** `MagazineController`, `Magazine` model

## Magazine page

Shows:

- Cover and description
- List of published stories assigned to the magazine
- Member list (owner, editors)
- Follow/subscribe actions where applicable

## Magazine subdomain pages (optional)

When **`magazine_subdomains`** is enabled, each magazine is also served at `{slug}.{base-host}` (e.g. `http://the-commons.localhost:8888/`). **Approved** magazine posts use the magazine subdomain (`http://the-commons.localhost:8888/my-post`), not the author subdomain.

```bash
php artisan site:setting set magazine_subdomains on
php artisan site:setting set author_subdomain_base_host localhost
php artisan site:setting set magazine_subdomain_redirect on
```

See [Site settings & CLI](24-site-settings.md).

**Key files:** `App\Support\MagazineSubdomain`, `ServeMagazineSubdomain` middleware, `App\Support\PostUrl`

## Submit a story

Authors can submit an existing published post to a magazine.

| Route | `POST /magazine/{slug}/posts/{post}/submit` |
| **Who** | Post author (verified) |

Submitted posts move to draft with `pending` magazine status. Owners review them at `/magazine/{slug}/submissions`.

Submissions are tracked on the `posts` table via `magazine_id` and `magazine_submission_status`.

**Key files:** `MagazineController`, `MagazineSubmissionStatus` enum

## Join requests

Users can request to join a magazine as an editor.

| Route | `POST /magazine/{slug}/join-request` |
| **Who** | Verified users |

Owners approve or reject via notifications and magazine management UI.

**Key files:** `MagazineMember` model, join request handling in `MagazineController`

## Roles

| Role | Permissions |
|------|-------------|
| Owner | Full control, approve submissions, manage members |
| Editor | Contribute and moderate (per policy) |
| Member | Basic participation |

**Key files:** `MagazinePolicy`, `MagazineMember` pivot

## Related docs

- [Posts & authoring](03-posts-and-authoring.md)
- [Notifications](09-notifications.md)
