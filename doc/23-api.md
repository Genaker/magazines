# HTTP API

## Overview

Versioned JSON API under **`/api/v1`**. Authentication uses **Laravel Sanctum** personal access tokens (Bearer) or session auth in tests.

| | |
|---|---|
| **Base URL** | `/api/v1` |
| **Auth** | `Authorization: Bearer <token>` |
| **Format** | JSON request/response |

Future routes belong in `routes/api/v1.php` with controllers under `App\Http\Controllers\Api\V1\`.

## Authentication

Issue a token for a user:

```bash
php artisan app:api:token author@example.com --name=mobile-app
```

The plain-text token is shown once. Send it on every request:

```http
Authorization: Bearer 1|your-token-here
Accept: application/json
```

Requirements:

- User email must be verified (`verified` middleware)
- User must not be banned
- Application must be installed (users exist)

## Create post

**`POST /api/v1/posts`**

Creates an **article** post for the authenticated user.

### Request body

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `title` | string | yes | Max 255 |
| `body` | string | yes | HTML allowed |
| `category_id` | integer | yes | Must exist |
| `status` | string | yes | `draft`, `published`, or `unlisted` |
| `subtitle` | string | no | |
| `tags` | string[] | no | Max tags from `config/media.php` |
| `author_alias_id` | integer | no | Must belong to the user |
| `magazine_id` | integer | no | |
| `publish_at` | ISO 8601 date | no | For scheduled publish |

### Example

```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My API Post",
    "body": "<p>Hello world</p>",
    "category_id": 1,
    "status": "published",
    "tags": ["tech", "api"]
  }'
```

### Response `201`

```json
{
  "data": {
    "id": 42,
    "type": "article",
    "title": "My API Post",
    "slug": "my-api-post",
    "status": "published",
    "url": "http://127.0.0.1:8000/@username/my-api-post",
    "tags": ["tech", "api"],
    "created_at": "2026-06-16T12:00:00+00:00"
  }
}
```

## Create magazine

**`POST /api/v1/magazines`** (requires `magazines` feature)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | yes | Max 120 |
| `description` | string | no | Max 2000 |
| `custom_fields` | object | no | When configured in admin |

Authenticated user becomes magazine **owner**.

## Create site category

**`POST /api/v1/site-categories`** — **admin only** (site navigation categories, `magazine_id = null`)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | yes | Max 100 |
| `parent_id` | integer | no | Another site category |
| `description` | string | no | |
| `icon` | string | no | Max 50 |
| `sort_order` | integer | no | Default 0 |

## Create magazine category

**`POST /api/v1/magazines/{magazine}/categories`** (requires `magazines` feature)

Same body as site categories. Caller must be magazine **owner** or **editor**. `parent_id` must belong to the same magazine.

## Key files

| File | Role |
|------|------|
| `routes/api.php` | API entry, version prefix |
| `routes/api/v1.php` | v1 route definitions |
| `config/api.php` | Version prefix, default token abilities |
| `app/Services/PostCreator.php` | Shared post creation logic |
| `app/Http/Controllers/Api/V1/PostController.php` | v1 post endpoints |
| `app/Http/Controllers/Api/V1/MagazineController.php` | v1 magazine endpoints |
| `app/Http/Controllers/Api/V1/CategoryController.php` | v1 category endpoints |
| `app/Http/Requests/Api/V1/StorePostRequest.php` | Post validation |
| `app/Http/Resources/Api/V1/PostResource.php` | Post JSON shape |
| `app/Http/Resources/Api/V1/MagazineResource.php` | Magazine JSON shape |
| `app/Http/Resources/Api/V1/CategoryResource.php` | Category JSON shape |
| `app/Console/Commands/CreateApiTokenCommand.php` | `app:api:token` |
| `tests/Feature/Api/V1/CreatePostTest.php` | Feature tests |

## Related docs

- [Posts & authoring](03-posts-and-authoring.md)
- [Authentication](02-authentication.md)
- [Testing](15-testing.md)
