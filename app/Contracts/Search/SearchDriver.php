<?php

namespace App\Contracts\Search;

use App\Models\AuthorAlias;
use App\Models\Magazine;
use App\Models\Post;
use Illuminate\Support\Collection;

interface SearchDriver
{
    public function isAvailable(): bool;

    /** @return Collection<int, Post> */
    public function searchPosts(string $query, int $limit = 20): Collection;

    /** @return Collection<int, AuthorAlias> */
    public function searchAuthors(string $query, int $limit = 10): Collection;

    /** @return Collection<int, Post> */
    public function relatedPosts(Post $post, int $limit = 6): Collection;

    public function indexPost(Post $post): void;

    public function removePost(Post $post): void;

    public function indexAuthor(AuthorAlias $alias): void;

    public function ensureIndexes(): void;

    public function reindexAll(): void;
}
