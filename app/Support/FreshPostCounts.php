<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Re-load views_count and likes_count from the database for cached Post models. */
class FreshPostCounts
{
    /** Walk cached posts, collections, or paginators and refresh volatile counts in place. */
    public static function hydrate(mixed $value): mixed
    {
        $posts = self::collectPosts($value); // flatten Post instances from mixed cache shapes

        if ($posts->isEmpty()) {
            return $value;
        }

        // Fresh counts keyed by post id (single query for all cached posts)
        $counts = Post::query()
            ->whereIn('id', $posts->pluck('id')->unique()->all())
            ->get(['id', 'views_count', 'likes_count'])
            ->keyBy('id');

        foreach ($posts as $post) {
            $row = $counts->get($post->id); // id, views_count, likes_count only

            if ($row === null) {
                continue;
            }

            $post->views_count = $row->views_count;
            $post->likes_count = $row->likes_count;
        }

        return $value;
    }

    /**
     * Collect Post models from a single model, collection, or paginator.
     *
     * @return Collection<int, Post>
     */
    private static function collectPosts(mixed $value): Collection
    {
        if ($value instanceof Post) {
            return collect([$value]);
        }

        if ($value instanceof EloquentCollection) {
            return $value->filter(fn (Model $model) => $model instanceof Post)->values();
        }

        if ($value instanceof LengthAwarePaginator) {
            return collect($value->items())->filter(fn ($item) => $item instanceof Post)->values();
        }

        return collect();
    }
}
