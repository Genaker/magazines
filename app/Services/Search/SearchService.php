<?php

namespace App\Services\Search;

use App\Contracts\Search\SearchDriver;
use App\Models\Magazine;
use App\Models\Post;
use App\Support\DatabaseFullTextSearch;
use App\Support\Features;
use App\Support\SearchTextPreprocessor;
use Illuminate\Support\Collection;

/** Resolves search driver and searches posts, authors, magazines. */
class SearchService
{
    public function __construct(
        private RedisSearchDriver $redis,
        private DatabaseSearchDriver $database,
        private SearchTextPreprocessor $preprocessor,
    ) {}

    public function driver(): SearchDriver
    {
        if (config('search.driver') === 'redis' && $this->redis->isAvailable()) {
            return $this->redis;
        }

        return $this->database;
    }

    /** @return Collection<int, Post> */
    public function searchPosts(string $query): Collection
    {
        return $this->driver()->searchPosts(
            $query,
            (int) config('search.limits.posts', 20),
        );
    }

    /** @return Collection<int, \App\Models\AuthorAlias> */
    public function searchAuthors(string $query): Collection
    {
        return $this->driver()->searchAuthors(
            $query,
            (int) config('search.limits.authors', 10),
        );
    }

    /** @return Collection<int, Magazine> */
    public function searchMagazines(string $query): Collection
    {
        $query = $this->preprocessor->forFullTextQuery(trim($query));

        if ($query === '' || ! Features::enabled('magazines')) {
            return collect();
        }

        return Magazine::query()
            ->where(function ($builder) use ($query): void {
                DatabaseFullTextSearch::matchAny($builder, ['name', 'description', 'slug'], $query);
            })
            ->orderBy('name')
            ->limit((int) config('search.limits.magazines', 10))
            ->get();
    }

    /** @return Collection<int, Post> */
    public function relatedPosts(Post $post): Collection
    {
        return $this->driver()->relatedPosts(
            $post,
            (int) config('search.limits.related', 6),
        );
    }

    public function indexPost(Post $post): void
    {
        if (config('search.driver') !== 'redis') {
            return;
        }

        $this->redis->indexPost($post);
    }

    public function removePost(Post $post): void
    {
        if (config('search.driver') !== 'redis') {
            return;
        }

        $this->redis->removePost($post);
    }
}
