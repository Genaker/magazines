# Posts & Authoring

## Post URLs

Published and unlisted stories use clean URLs:

```
/@{username}/{slug}
```

Example: `/@demoauthor/why-writing-matters`

## Post statuses

| Status | Who can view | In feed/search |
|--------|--------------|----------------|
| **Draft** | Author and admins only | No |
| **Published** | Everyone (once `published_at` has passed) | Yes (when live) |
| **Scheduled** | Author and admins only until `published_at` | No until go-live |
| **Unlisted** | Anyone with the link | No |

Draft and unlisted posts show a “shareable by link” banner for authorized viewers. Scheduled posts show a go-live time to the author.

## Post types

| Type | Route | Storage |
|------|-------|---------|
| **Article** (default) | `GET /write` | HTML body in TinyMCE |
| **Gallery** | `GET /write/gallery` | Photos in `post_gallery_items` on the configured media disk (`MEDIA_DISK`, default `public`; S3-ready) |
| **Video** | `GET /write/video` | `video_url` on the post; embed rendered at the top of the show page |

Gallery and video posts use the same URLs, feeds, author profiles, likes, and comments as articles.

### Gallery posts

Photos are processed with responsive variants (`sm` / `md` / `lg`) via `ImageService`. The show page uses a masonry portfolio grid and **lightGallery** lightbox.

Publish requires at least `MIN_GALLERY_PHOTOS` images (default 2). The first photo becomes the feed cover unless a custom cover is uploaded.

Each photo can have a **caption** (alt text and visible figcaption). Authors reorder new uploads before publish; the first visible photo is labeled **Cover** in the composer.

**Key files:** `PostType` enum, `GalleryService`, `PostGalleryItem`, `resources/js/gallery-composer.js`, `posts/create-gallery.blade.php`, `partials/portfolio-gallery.blade.php`

### Video posts

Paste a URL from a supported provider (YouTube, Vimeo, Dailymotion, Wistia, Twitch, Streamable, Loom, Facebook Watch). The player is embedded **above** the optional rich-text description.

Publish requires a supported `video_url`. Drafts may omit the URL until you are ready.

**Key files:** `App\Support\VideoEmbed`, `posts/create-video.blade.php`, `partials/video-embed.blade.php`

### Image processing (all uploads)

Post covers, editor uploads, and gallery photos respect **Admin → Settings → Images** (or `config/media.php` defaults):

| Setting | Default |
|---------|---------|
| Max dimensions | 1500×1500 px |
| JPEG quality | 85% |
| Responsive variants | sm 480 / md 800 / lg 1500 px |

**Do not resize** (per post/gallery preset) stores files at original dimensions without variants.

**Key files:** `App\Support\MediaSettings`, `ImageService`, `config/media.php`

### Publish at (no cron)

When status is **Published**, the optional **Publish at** field sets `published_at`. A future datetime keeps the story hidden from feeds, search, and public URLs until that time — visibility is enforced at query time (`published_at <= now()`), not by a background job.

## Writing flow

| Action | Route | Who |
|--------|-------|-----|
| My stories | `GET /me/posts` | Verified user |
| Create article | `GET/POST /write` | Verified user |
| Create gallery | `GET/POST /write/gallery` | Verified user |
| Create video | `GET/POST /write/video` | Verified user |
| Edit | `GET /write/{post}/edit`, `PUT /write/{post}` | Author or admin |
| Delete | `DELETE /write/{post}` | Author or admin |
| View | `GET /@{user}/{slug}` | Public (policy-gated) |

## Editor features

### TinyMCE

Rich text editor with bold, lists, links, images, **embedded video** (TinyMCE `media` plugin — paste a YouTube/Vimeo URL or direct video link), code blocks, and a custom **Gallery** button (2+ images in a grid with lightbox).

**Key files:** `resources/js/editor.js`, `resources/js/gallery.js`, `resources/views/posts/create.blade.php`, `resources/views/posts/edit.blade.php`

### Autosave

Saves title, subtitle, body, category, tags, and cover every 2 seconds (and every 15 seconds on interval) without publishing.

| Route | `POST /write/autosave` |
| **Response** | JSON with `post_id`, `revision`, `saved_at` |

Conflict detection returns HTTP 409 if a newer revision exists elsewhere.

**Key files:** `PostController::autosave`, `AutosaveService`, `PostAutosaveSnapshot` model

### Cover image

Drag-and-drop or click upload on the write page. Processed into responsive variants (sm/md/lg). Files are stored on the **`public`** disk (`MEDIA_DISK=public`) and served from `/storage/media/...`.

**Key files:** `resources/views/partials/cover-upload.blade.php`, `ImageService`, `config/media.php`

### Inline images

Uploaded via `POST /media/upload` from the TinyMCE image dialog.

### Embedded video

Use the **Insert/edit video** toolbar button (`media` plugin). Paste a video URL (YouTube, Vimeo, or direct `.mp4`/`.webm` link). TinyMCE embeds it as an `<iframe>` or `<video>` with a live preview in the editor. Published posts render embeds responsively inside `.prose`.

### Tags

Comma-separated on the write form. Maximum count from `config/media.php` (`MAX_TAGS_PER_POST`, default 10).

### AI writing & grammar assistants

On the write page:

1. **Task** — *Assist writing* or *Check grammar* (different prompt templates)
2. **Your instructions** — optional short text prepended before title, tags, and draft body
3. **AI service** — dropdown (ChatGPT, Claude, Gemini, Grok, etc.)
4. **Copy & open** — copies the full prompt to the clipboard and opens the selected service in a new tab. Paste with **Cmd+V / Ctrl+V** into the chat (shown on the button tooltip).
5. **Copy prompt** — copies only, without opening a tab

**Key files:** `config/ai-writing-tools.php`, `resources/views/partials/ai-writing-tools.blade.php`, `resources/js/editor.js`

## View counts

Each unique IP per day increments `views_count` on the post. Mode is controlled by `POST_VIEW_COUNT_MODE` (`sync` default, `async` for queue).

**Key files:** `config/post-views.php`, `PostViewRecorder`, `RecordPostView` job, `PostView` model

## SEO

Post pages include Open Graph and Twitter meta tags.

**Key files:** `app/Support/Seo.php`, `resources/views/partials/meta-tags.blade.php`

## Policies

`PostPolicy` controls view, update, and delete. Guests cannot view drafts unless they are the author.

**Key files:** `app/Policies/PostPolicy.php`, `app/Models/Post.php`

## Related docs

- [Feed & discovery](04-feed-and-discovery.md)
- [Reading lists](08-reading-lists.md)
- [Magazines](10-magazines.md) — submit stories to publications
