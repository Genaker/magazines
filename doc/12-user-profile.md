# User Profile (Account Settings)

## Overview

Logged-in users manage their account, appearance, and privacy from profile settings — separate from the public author page at `/@{username}`.

| | |
|---|---|
| **Route** | `GET /profile`, `PATCH /profile` |
| **Who** | Authenticated users |

## Editable fields

| Field | Notes |
|-------|-------|
| Display name | Shown on posts and profile |
| Username | `@handle` in URLs; uniqueness enforced |
| Email | Changing email requires re-verification |
| Bio | Short author description |
| Avatar | Image upload |

**Key files:** `ProfileController`, `resources/views/profile/edit.blade.php`, `ImageService`

## Password

Update via `PUT /password` (requires current password). See [Authentication](02-authentication.md).

## Delete account

Users can permanently delete their account from profile settings. Soft-deleted users are handled per admin trash workflow.

**Key files:** `ProfileController::destroy`, `User` soft deletes

## Blocked users

Blocked users are listed on the profile edit page (`GET /profile`). Unblock via:

| Route | `DELETE /profile/blocked-users/{user}` |

Block from any author profile: `POST /users/{user}/block`.

**Key files:** `UserBlockController`, `resources/views/profile/partials/blocked-users.blade.php`

## My content shortcuts

| Route | Purpose |
|-------|---------|
| `/me/posts` | Your drafts and published stories |
| `/me/lists` | Reading lists |

## Related docs

- [Author profiles](11-author-profiles.md)
- [Reading lists](08-reading-lists.md)
- [Authentication](02-authentication.md)
