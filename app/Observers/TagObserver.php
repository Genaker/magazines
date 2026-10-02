<?php

namespace App\Observers;

use App\Models\Tag;
use App\Support\EntityCache;

/** Invalidates tag and feed caches when tags change. */
class TagObserver
{
    /** Invalidate caches after a tag is created or updated. */
    public function saved(Tag $tag): void
    {
        $this->flushCaches();
    }

    /** Invalidate caches after a tag is deleted. */
    public function deleted(Tag $tag): void
    {
        $this->flushCaches();
    }

    /** Flush tag, post, and feed cache tags. */
    private function flushCaches(): void
    {
        EntityCache::flushTags([
            config('entity-cache.tags.tags'),
            config('entity-cache.tags.posts'),
            config('entity-cache.tags.feeds'),
        ]);
    }
}
