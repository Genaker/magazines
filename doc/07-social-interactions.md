# Social Interactions

## Follow authors

| | |
|---|---|
| **Route** | `POST /users/{user}/follow` |
| **Who** | Verified users |
| **Effect** | Toggle follow; updates follower counts; triggers notification |

Following affects the home feed and shows posts from that author first.

**Key files:** `FollowController`, `Follow` model (polymorphic)

## Follow categories

| Route | `POST /categories/{category}/follow` |
| **Who** | Verified users |

See [Categories & tags](06-categories-and-tags.md).

## Follow tags

| Route | `POST /tags/{tag}/follow` |
| **Who** | Verified users |

Tagged posts from followed tags appear in your personalized feed.

## Follow magazines

| Route | `POST /magazines/{magazine}/follow` |
| **Who** | Verified users |

## Likes (claps)

| | |
|---|---|
| **Route** | `POST /posts/{post}/like` |
| **Who** | Guests (by IP) and logged-in users |
| **Effect** | Toggle like; updates `likes_count` |

Guests can like without an account. Duplicate likes from the same IP or user are prevented.

**Key files:** `LikeController`, `Like` model, `public/js/post.js`

## Comments

| Action | Route | Who |
|--------|-------|-----|
| View | On post page (`GET /@{user}/{slug}`) | Everyone |
| Create | `POST /posts/{post}/comments` | Verified users |
| Delete | `DELETE /comments/{comment}` | Comment author or admin |

Comments render on the post page below the story body. Creating a comment notifies the post author; replies notify the parent comment author.

### Disqus (optional)

Super admins can switch to **Disqus** embeds instead of built-in comments:

| Setting | Purpose |
|---------|---------|
| `comments_use_disqus` | Enable Disqus on post pages |
| `disqus_shortname` | Your Disqus forum shortname |

When enabled, built-in comment routes are disabled and the post page shows the Disqus widget (`partials/disqus-comments.blade.php`).

**Key files:** `CommentController`, `Comment` model, `CommentPolicy`, `App\Support\CommentSettings`, `Admin\SiteSettingController`

## Block users

| | |
|---|---|
| **Route** | `POST /users/{user}/block` |
| **Who** | Verified users |
| **Effect** | Hides their posts from your feed; prevents interaction |

Blocked users can be managed and unblocked from profile settings.

**Key files:** `BlockController`, `UserBlock` model, [User profile](12-user-profile.md)

## Report a user

| Route | `POST /users/{user}/report` |
| **Who** | Verified users |

Reports appear in the admin user-reports queue (`/admin/user-reports`).

**Key files:** `UserReportController`, `UserReport` model, `Admin\UserReportController`

## Subscribe to author stories

| Route | `POST /users/{user}/subscribe` |
| **Who** | Verified users |

Toggles story subscription. The author receives an in-app notification when someone subscribes.

### Email alerts

Subscribers can choose how they are notified on the author profile:

| Delivery | Behavior |
|----------|----------|
| `instant` | Email when the author publishes (default) |
| `daily` | Queued for the daily digest cron |
| `off` | In-app subscribe only, no email |

| | |
|---|---|
| **Digest cron** | `subscriptions:send-digest` — daily via [scheduler](21-scheduler.md) |
| **Unsubscribe** | Signed link in instant emails → `GET /subscriptions/unsubscribe/{subscriber}/{author}` |

Configure in `config/subscriptions.php` (`SUBSCRIPTION_EMAIL_ENABLED`, `SUBSCRIPTION_DIGEST_TIME`).

**Key files:** `AuthorSubscriptionController`, `AuthorSubscriptionNotifier`, `author_subscriptions` table, `subscription_digest_items` table

## Related docs

- [Notifications](09-notifications.md)
- [Reading lists](08-reading-lists.md)
- [Admin panel](13-admin.md) — handle reports
