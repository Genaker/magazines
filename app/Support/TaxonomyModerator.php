<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

/** Permission checks for delegated category and tag moderators. */
class TaxonomyModerator
{
    public static function canModerateCategory(User $user, Category $category): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return static::moderatedCategoryIds($user)->contains($category->id);
    }

    public static function canModerateTag(User $user, Tag $tag): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->moderatedTags()->whereKey($tag->id)->exists();
    }

    public static function canModeratePost(User $user, Post $post): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($post->category_id && static::moderatedCategoryIds($user)->contains($post->category_id)) {
            return true;
        }

        $post->loadMissing('tags');

        return $post->tags->contains(
            fn (Tag $tag) => $user->moderatedTags()->whereKey($tag->id)->exists(),
        );
    }

    public static function isTaxonomyModerator(User $user): bool
    {
        return $user->moderatedCategories()->exists() || $user->moderatedTags()->exists();
    }

    /** Category IDs the user can moderate, including descendant posts. */
    public static function moderatedCategoryScopeIds(User $user): array
    {
        $ids = [];

        foreach ($user->moderatedCategories()->pluck('categories.id') as $categoryId) {
            $ids = array_merge($ids, Category::descendantIdsFor((int) $categoryId));
        }

        return array_values(array_unique($ids));
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private static function moderatedCategoryIds(User $user): \Illuminate\Support\Collection
    {
        return collect(static::moderatedCategoryScopeIds($user));
    }
}
