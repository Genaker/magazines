# Extensibility

The app uses **Laravel events and listeners** as extension points. After a domain action completes, an event is dispatched; side effects (notifications, emails, analytics) live in listeners under `app/Listeners/`.

No module system, no custom hook registry — add a listener class and Laravel auto-discovers it.

## Domain events

| Event | Dispatched from | Typical listeners |
|-------|-----------------|-------------------|
| `PostPublished` | `PostObserver` when a post is first published | `NotifySubscribersOfPublishedPost` |
| `PostViewed` | `RecordPostView` job after a unique view | (add your own) |
| `CommentCreated` | `CommentController@store` | `NotifyCommentParticipants` |
| `CommentLiked` | `CommentLikeController@toggle` | `NotifyCommentAuthorOfLike` |
| `UserFollowed` | `FollowController@toggleUser` | `NotifyUserOfFollow` |
| `AuthorSubscribed` | `AuthorSubscriptionController@toggle` | `NotifyAuthorOfSubscription` |

## Adding behavior

1. Create a listener in `app/Listeners/`:

```php
namespace App\Listeners;

use App\Events\CommentCreated;

class LogCommentCreated
{
    public function handle(CommentCreated $event): void
    {
        // $event->comment
    }
}
```

2. Laravel 13 auto-discovers listeners — no manual registration.

3. To toggle behavior site-wide, use existing **feature flags** (`config/features.php`, admin Site Settings) inside your listener:

```php
if (! Features::enabled('comments')) {
    return;
}
```

## Feature flags and routes

- `@feature('name')` in Blade — hide UI
- `middleware('feature:name')` on routes — return 404 when disabled
- `Features::enabled('name')` in PHP

## Blade hook placeholders

Named slots in layouts and pages render registered partials. Slot names are defined in `config/hooks.php`.

**Register a partial** (from `AppServiceProvider::boot()` or any service provider):

```php
use App\Support\ViewHooks;

ViewHooks::register('post.after_content', 'partials.my-promo');
// or: config()->push('hooks.post.after_content', 'partials.my-promo');
```

**Pass data** from the page template (already wired on post hooks):

```blade
<x-hook name="post.after_content" :data="['post' => $post]" />
```

Your partial receives those variables:

```blade
{{-- resources/views/partials/my-promo.blade.php --}}
@if ($post->isPublished())
    <aside>{{ $post->title }} — sponsored</aside>
@endif
```

### Available slots

| Slot | Location |
|------|----------|
| `head.after_meta` | After SEO meta tags in site layout |
| `body.start` / `body.end` | Start/end of `<body>` |
| `nav.after` | After top navigation |
| `main.before` / `main.after` | Around `<main>` content |
| `footer.before` | Before site footer |
| `post.before_content` / `post.after_content` | Around post body |
| `post.after_comments` | After comments section |
| `profile.sidebar.after` | After profile sidebar links |
| `admin.content.before` | Before admin main content |

Multiple views can register to the same slot; they render in registration order.

## Template / theme overrides

Override any Blade view by mirroring its path under `templates/{name}/`. Set the active theme:

```env
SITE_TEMPLATE=magazine
```

Laravel resolves `templates/magazine/` **before** `resources/views/`. Missing files fall back to the default. See `templates/README.md`.

```php
use App\Support\Theme;

Theme::name();      // active slug or null
Theme::available(); // folder names under templates/
```

## What not to use

- Do not edit controllers for new side effects — add a listener instead.
- No plugin folders or manifest files — standard Laravel directories only.
