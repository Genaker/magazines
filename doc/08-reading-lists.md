# Reading Lists

## Overview

Reading lists replace the old single bookmark feature. Users can save stories to **multiple named lists**, with a multi-list save flow.

Each user gets a default private list named **"Reading list"** automatically when they first save a post.

## Save a story

On any post page, the **Save** button opens a popover:

- Checkboxes for existing lists (toggle membership)
- **Create new list** opens a modal; the current post is added automatically on create

| Route | Purpose |
|-------|---------|
| `POST /posts/{post}/bookmark` | Toggle post in default list (Save button) |
| `POST /me/lists/{list}/posts/{post}` | Add/remove post from a specific list |
| `POST /me/lists` | Create a new list (optional `post_id` to auto-add) |

**Key files:** `ReadingListController`, `resources/views/partials/reading-list-picker.blade.php`, `public/js/post.js`

## Manage lists

| Route | Purpose |
|-------|---------|
| `GET /me/lists` | All your lists |
| `GET /me/lists/{list}` | Stories in one list |
| `PATCH /me/lists/{list}` | Rename or change privacy |
| `DELETE /me/lists/{list}` | Delete list (posts are not deleted) |

### Privacy

Lists can be **public** or **private**. Private lists are only visible to the owner.

## Legacy bookmarks redirect

`GET /me/bookmarks` redirects to `/me/lists` for backward compatibility.

## Data model

| Table | Purpose |
|-------|---------|
| `reading_lists` | `user_id`, `name`, `slug`, `is_private` |
| `reading_list_post` | Pivot: list ↔ post |

Existing `bookmarks` were migrated into default reading lists via migration.

**Key files:** `ReadingList` model, `ReadingListPolicy`, migrations

## Authorization

`ReadingListPolicy` ensures users can only view and edit their own lists.

## Related docs

- [Posts & authoring](03-posts-and-authoring.md)
- [User profile](12-user-profile.md)
