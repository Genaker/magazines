# Categories & Tags

## Categories

### Hierarchy

Categories form a two-level tree:

- **Parent** categories (e.g. Technology, Culture)
- **Subcategories** (e.g. Web Development under Technology)

Posts are assigned to **one** category (typically a subcategory).

### Category pages

| Route | Purpose |
|-------|---------|
| `GET /category/{slug}` | List published posts in category |
| `POST /categories/{category}/follow` | Toggle follow (verified users) |

Following a category affects the personalized home feed.

**Key files:** `CategoryController`, `Category` model, `resources/views/categories/show.blade.php`

### Request a new category

Verified users can propose categories not yet on the site.

| Route | Purpose |
|-------|---------|
| `GET /category/request` | Submission form |
| `POST /category/request` | Submit request |
| `GET /me/category-requests` | Your submitted requests |

Admins review requests in the admin panel (approve → creates category, reject → notifies user).

**Key files:** `CategoryRequestController`, `CategoryRequest` model, `CategoryRequestSubmitted` / `CategoryRequestReviewed` notifications

## Tags

Tags are free-form labels attached to posts (many-to-many via `post_tag`). In the UI, tag names are shown with a **`#` prefix** (e.g. `#laravel`) via `Tag::getDisplayNameAttribute()`.

### Tag pages

| Route | `GET /tag/{slug}` |
| **Content** | Published posts with that tag |

Tags are normalized to lowercase slugs on save. Maximum tags per post is configurable (`MAX_TAGS_PER_POST`).

### Popular tags (home sidebar)

The home feed sidebar shows a **Popular tags** cloud: tags with the most published posts, linking to each tag page. See [Feed & discovery](04-feed-and-discovery.md).

**Key files:** `TagController`, `Tag` model, `Post::syncTags()`, `FeedService::popularTagCloud()`

## Admin category management

Admins can create, edit, reorder, and delete categories from `/admin/categories`.

**Key files:** `Admin\CategoryController`, [Admin panel](13-admin.md)

## Related docs

- [Feed & discovery](04-feed-and-discovery.md)
- [Admin panel](13-admin.md)
