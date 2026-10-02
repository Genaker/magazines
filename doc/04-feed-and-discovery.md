# Feed & Discovery

## Home feed

| | |
|---|---|
| **Route** | `GET /` (`/discover` redirects here) |
| **Who** | Everyone (personalized sections when logged in) |

The home page shows **sections** of story cards (not one flat paginated list). Super admins choose the layout under **Admin → Settings → Home page**:

| Layout | What visitors see |
|--------|-------------------|
| **Discover** (default) | Personalized feed (when logged in) plus week, hour, day, and latest sections |
| **Latest only** | Single list of the 20 newest published stories |
| **Trending only** | Top stories this week, trending last hour, and trending today |

### Guest / logged-out sections

| Section | Content |
|---------|---------|
| Top stories this week | Most viewed posts in the last 7 days (`post_views.viewed_on`); falls back to all-time most popular (`views_count`) when nothing was viewed this week |
| Trending last hour | Most viewed in the rolling last 60 minutes (`post_views.created_at`); all-time most popular when nothing was viewed this hour |
| Trending today | Most viewed in the rolling last 24 hours (`post_views.created_at`); all-time most popular when nothing was viewed today |
| Latest | Newest published stories |

### Personalized sections (logged in)

Above the discover sections, logged-in users also see:

| Section | Content |
|---------|---------|
| Latest from your network | Newest posts from **authors, categories, tags, and magazines you follow** |
| Popular in your network | Top posts from your follows in the last week (by views, then likes) |

**Key files:** `FeedService`, `FeedController`, `resources/views/feed/home.blade.php`

## Popular tags (tag cloud)

On **Discover** and **Trending** home layouts, the sidebar lists up to 20 tags ranked by published post count. Each link shows `#tagname (N)` and goes to the tag page.

Logged-out guests and **Latest only** layout do not show the tag cloud (sidebar is omitted).

**Key files:** `FeedService::popularTagCloud()`, `resources/views/partials/tag-cloud.blade.php`

## Story cards

Each card links to `/@{username}/{slug}` and may show:

- Cover image thumbnail
- Title and subtitle excerpt
- Author name and avatar
- Category badge
- Estimated read time
- Like count and comment count

## Caching

Section post IDs are cached via `EntityCache` (Redis-backed) with configurable TTL. Posts are loaded by ID with eager loading for authors, categories, and tags.

## Related docs

- [Social interactions](07-social-interactions.md) — follow authors and categories
- [Categories & tags](06-categories-and-tags.md)
- [Search](05-search.md)
