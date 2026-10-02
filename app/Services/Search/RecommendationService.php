<?php

namespace App\Services\Search;

use App\Models\AuthorAlias;
use App\Models\Post;
use Illuminate\Support\Collection;

/** Tag/category overlap recommendations without vectors. */
class RecommendationService
{
    /** @return Collection<int, Post> */
    public function relatedByTaxonomy(Post $post, int $limit = 6): Collection
    {
        $post->loadMissing(['tags', 'category']);

        $tagIds = $post->tags->pluck('id');

        $query = Post::query()
            ->with(['user', 'authorAlias', 'category', 'tags'])
            ->published()
            ->visibleInFeeds()
            ->whereKeyNot($post->id);

        if ($tagIds->isNotEmpty()) {
            $query->where(function ($builder) use ($tagIds, $post) {
                $builder->whereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $tagIds));

                if ($post->category_id) {
                    $builder->orWhere('category_id', $post->category_id);
                }
            })
                ->orderByRaw(
                    '(select count(*) from post_tag where post_tag.post_id = posts.id and post_tag.tag_id in ('.
                    $tagIds->implode(',').')) desc',
                );
        } elseif ($post->category_id) {
            $query->where('category_id', $post->category_id);
        } else {
            return collect();
        }

        return $query
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
