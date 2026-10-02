# Notifications

## Overview

In-app notifications alert users to social activity. A bell icon in the navigation shows unread count and a dropdown of recent items.

| | |
|---|---|
| **Route** | `GET /me/notifications` (full page), bell dropdown in nav |
| **Who** | Verified users |

## Notification types

| Kind | Trigger |
|------|---------|
| `follow` | Someone follows you |
| `story_subscription` | Someone subscribes to your stories |
| `like` | Someone claps on your post |
| `bookmark` | Someone saves your post to a reading list |
| `comment` | Someone comments on your post |
| `comment_reply` | Someone replies to your comment |
| `magazine_join_request` | User requests to join your magazine |
| `magazine_join_approved` | Owner approves your join request |

Category request outcomes use a separate email notification (`CategoryRequestReviewed`), not the activity bell.

Blocked users do not send or receive activity notifications.

**Key files:** `ActivityNotifier`, `UserActivity` notification, `NotificationController`, `resources/views/partials/notification-item.blade.php`

## Bell dropdown

Shows the latest notifications with:

- Actor avatar and name
- Short message and relative time
- Link to the relevant post, profile, or admin page
- Mark individual items as read on click

## Mark as read

| Route | Purpose |
|-------|---------|
| `POST /me/notifications/{id}/read` | Mark one notification read |
| `POST /me/notifications/read` | Mark all read |

Uses Laravel's `notifications` table (`read_at` on each row).

## Email

Some events also send email (magic link, category request updates). Emails are sent **synchronously** — no queue worker required.

**Key files:** `app/Notifications/`, `config/mail.php`

## Related docs

- [Social interactions](07-social-interactions.md)
- [Magazines](10-magazines.md)
- [Categories & tags](06-categories-and-tags.md)
