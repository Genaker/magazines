<?php

namespace App\Observers;

use App\Enums\PostStatus;
use App\Events\PostPublished;
use App\Jobs\IndexPostForSearch;
use App\Models\AuthorAlias;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Support\EntityCache;

/**
 * Maintains post slugs, reading time, and cache coherence on save.
 *
 * Dispatches PostPublished when a post becomes published for the first time.
 */
class PostObserver
{
    /** Clear the show-page cache for the previous slug when it changes. */
    public function updating(Post $post): void
    {
        if ($post->isDirty('slug') || $post->isDirty('author_alias_id')) {
            $this->forgetShowCache($post->getOriginal('author_alias_id'), $post->getOriginal('slug'));
        }
    }

    /** Auto-generate slug, reading time, and clear pin when unpublished. */
    public function saving(Post $post): void
    {
        if (blank($post->slug) && filled($post->title) && $post->author_alias_id) {
            $post->slug = Post::uniqueSlugForAlias($post->title, $post->author_alias_id, $post->id);
        }

        $wordCount = str_word_count(strip_tags((string) $post->body)); // ~200 wpm reading estimate
        $post->reading_time = max(1, (int) ceil($wordCount / 200));

        if ($post->status !== PostStatus::Published) {
            $post->pinned_at = null;
        }
    }

    /** Flush feed/post caches after save; dispatch publish event when newly published. */
    public function saved(Post $post): void
    {
        $this->flushCaches($post);
        $this->queueSearchIndex($post);

        if ($this->shouldNotifySubscribers($post)) {
            PostPublished::dispatch($post);
        }
    }

    /** Register a redirect when the public post URL changes. */
    public function updated(Post $post): void
    {
        if (! $post->wasChanged('slug') && ! $post->wasChanged('author_alias_id')) {
            return;
        }

        $oldAliasId = $post->getOriginal('author_alias_id');
        $oldSlug = $post->getOriginal('slug');

        if (! $oldAliasId || ! $oldSlug) {
            return;
        }

        $oldUsername = AuthorAlias::withTrashed()->whereKey($oldAliasId)->value('username');

        if ($oldUsername) {
            PostRedirect::register($oldUsername, $oldSlug, $post);
        }
    }

    /** Invalidate caches when a post is soft-deleted. */
    public function deleted(Post $post): void
    {
        $this->flushCaches($post);
        $this->queueSearchIndex($post);
    }

    /** Invalidate caches when a soft-deleted post is restored. */
    public function restored(Post $post): void
    {
        $this->flushCaches($post);
        $this->queueSearchIndex($post);
    }

    /** Invalidate caches when a post is permanently removed. */
    public function forceDeleted(Post $post): void
    {
        $this->flushCaches($post);
        $this->queueSearchIndex($post);
    }

    /** Invalidate tagged caches unless only autosave metadata changed. */
    private function flushCaches(Post $post): void
    {
        if ($this->isAutosaveRevisionOnly($post)) {
            return;
        }

        $this->forgetShowCache($post->author_alias_id, $post->slug);

        EntityCache::flushTags([
            config('entity-cache.tags.posts'),
            config('entity-cache.tags.feeds'),
            config('entity-cache.tags.authors'),
        ]);
    }

    /** Skip cache flush when only the autosave revision counter changed. */
    private function isAutosaveRevisionOnly(Post $post): bool
    {
        $changes = array_keys($post->getChanges()); // dirty columns on this save

        return $changes !== []
            && empty(array_diff($changes, ['autosave_revision', 'updated_at']));
    }

    /** Drop the per-post show page cache entry. */
    private function forgetShowCache(?int $aliasId, ?string $slug): void
    {
        if ($aliasId && $slug) {
            EntityCache::forget(EntityCache::key('posts', 'show', $aliasId, $slug));
        }
    }

    /**
     * Fire subscriber notifications once per publish transition.
     *
     * Covers both newly created published posts and drafts transitioning to published.
     */
    private function shouldNotifySubscribers(Post $post): bool
    {
        if (! $post->isPublished() || $post->subscription_notified_at !== null) {
            return false;
        }

        if ($post->wasRecentlyCreated) {
            return $post->status === PostStatus::Published;
        }

        if ($post->wasChanged('status')) {
            $original = $post->getOriginal('status'); // enum or raw string from DB
            $originalStatus = $original instanceof PostStatus
                ? $original
                : PostStatus::from($original);

            return $post->status === PostStatus::Published
                && $originalStatus !== PostStatus::Published;
        }

        return false;
    }

    private function queueSearchIndex(Post $post): void
    {
        if ($this->isAutosaveRevisionOnly($post) || config('search.driver') !== 'redis') {
            return;
        }

        IndexPostForSearch::dispatch($post->id);
    }
}
