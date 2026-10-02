<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\ImageService;
use App\Support\EntityCache;

/** Invalidates category navigation and related entity caches on changes. */
class CategoryObserver
{
    /** Invalidate caches after a category is created or updated. */
    public function saved(Category $category): void
    {
        $this->flushCaches();
    }

    /** Invalidate caches after a category is deleted. */
    public function deleted(Category $category): void
    {
        app(ImageService::class)->deleteVariants($category->image_variants);
        $this->flushCaches();
    }

    /** Clear navigation cache and tagged category/post/feed entries. */
    private function flushCaches(): void
    {
        EntityCache::forget(EntityCache::key('categories', 'navigation'));

        EntityCache::flushTags([
            config('entity-cache.tags.categories'),
            config('entity-cache.tags.posts'),
            config('entity-cache.tags.feeds'),
        ]);
    }
}
