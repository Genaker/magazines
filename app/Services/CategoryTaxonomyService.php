<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryRedirect;
use App\Support\EntityCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Merge, delete, and rebuild the category tree after taxonomy changes. */
class CategoryTaxonomyService
{
    public function merge(Category $from, Category $into): void
    {
        if ($from->id === $into->id) {
            throw ValidationException::withMessages([
                'merge_into_id' => 'Choose a different category to merge into.',
            ]);
        }

        if ($from->magazine_id !== $into->magazine_id) {
            throw ValidationException::withMessages([
                'merge_into_id' => 'Categories must belong to the same magazine scope.',
            ]);
        }

        if (Category::isDescendantOf($into, $from)) {
            throw ValidationException::withMessages([
                'merge_into_id' => 'Cannot merge a category into its own subcategory.',
            ]);
        }

        DB::transaction(function () use ($from, $into): void {
            $from->posts()->update(['category_id' => $into->id]);
            $from->children()->update(['parent_id' => $into->id]);
            CategoryRedirect::register($from->slug, $into);
            $from->delete();
            $this->rebuildTree();
        });
    }

    public function delete(Category $category, ?Category $fallback = null): void
    {
        if ($category->posts()->exists() && ! $fallback) {
            throw ValidationException::withMessages([
                'fallback_category_id' => 'Choose a category to receive this category\'s posts.',
            ]);
        }

        DB::transaction(function () use ($category, $fallback): void {
            $oldSlug = $category->slug;

            if ($fallback) {
                $category->posts()->update(['category_id' => $fallback->id]);
                $category->children()->update(['parent_id' => $fallback->id]);
                CategoryRedirect::register($oldSlug, $fallback);
            } else {
                $category->children()->update(['parent_id' => $category->parent_id]);
            }

            $category->delete();
            $this->rebuildTree();
        });
    }

    /** Flush category/post/feed caches after structural taxonomy changes. */
    public function rebuildTree(): void
    {
        EntityCache::forget(EntityCache::key('categories', 'navigation'));

        EntityCache::flushTags([
            config('entity-cache.tags.categories'),
            config('entity-cache.tags.posts'),
            config('entity-cache.tags.feeds'),
        ]);
    }
}
