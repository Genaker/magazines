<?php

namespace App\Services\Search;

use App\Contracts\Search\SearchDriver;
use App\Models\AuthorAlias;
use App\Models\Post;
use App\Services\Embeddings\EmbeddingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/** RediSearch full-text and optional vector hybrid search. */
class RedisSearchDriver implements SearchDriver
{
    public function __construct(
        private RedisSearchClient $redis,
        private PostSearchDocumentBuilder $postDocuments,
        private AuthorSearchDocumentBuilder $authorDocuments,
        private EmbeddingService $embeddings,
        private RecommendationService $recommendations,
    ) {}

    public function isAvailable(): bool
    {
        return $this->redis->moduleAvailable();
    }

    public function searchPosts(string $query, int $limit = 20): Collection
    {
        $query = trim($query);

        if ($query === '' || ! $this->isAvailable()) {
            return collect();
        }

        $this->ensureIndexes();

        $index = (string) config('search.indexes.posts');
        $redisQuery = $this->redis->buildPostQuery($query);

        if ($redisQuery === '') {
            return collect();
        }

        $vectorBlob = null;

        if ($this->embeddings->semanticEnabled()) {
            $vector = $this->embeddings->embedQuery($query);

            if ($vector !== []) {
                $vectorBlob = EmbeddingVector::pack($vector);
            }
        }

        $hits = $this->redis->searchPosts($index, $redisQuery, $limit, $vectorBlob);
        $ids = collect($hits)->pluck('id')->all();

        return $this->hydratePosts($ids);
    }

    public function searchAuthors(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if ($query === '' || ! $this->isAvailable()) {
            return collect();
        }

        $this->ensureIndexes();

        $hits = $this->redis->searchAuthors((string) config('search.indexes.authors'), $query, $limit);
        $ids = collect($hits)->pluck('id')->all();

        if ($ids === []) {
            return collect();
        }

        $aliases = AuthorAlias::query()
            ->active()
            ->listedInDirectory()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->filter(fn (int $id) => $aliases->has($id))
            ->map(fn (int $id) => $aliases->get($id))
            ->values();
    }

    public function relatedPosts(Post $post, int $limit = 6): Collection
    {
        if (! $this->isAvailable()) {
            return $this->recommendations->relatedByTaxonomy($post, $limit);
        }

        $this->ensureIndexes();

        if ($this->embeddings->semanticEnabled()) {
            $vector = $this->embeddings->embedForIndex(
                $this->postDocuments->embeddingText($post),
            );

            if ($vector !== []) {
                $hits = $this->redis->knnPosts(
                    (string) config('search.indexes.posts'),
                    EmbeddingVector::pack($vector),
                    $limit,
                    $post->id,
                );

                $ids = collect($hits)->pluck('id')->all();

                if ($ids !== []) {
                    return $this->hydratePosts($ids);
                }
            }
        }

        return $this->recommendations->relatedByTaxonomy($post, $limit);
    }

    public function indexPost(Post $post): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        $this->ensureIndexes();

        if (! $this->postDocuments->isSearchable($post)) {
            $this->removePost($post);

            return;
        }

        try {
            $this->redis->hashSet(
                $this->postDocuments->key($post),
                $this->postDocuments->build($post),
            );

            if ($post->authorAlias) {
                $this->indexAuthor($post->authorAlias);
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to index post for search', [
                'post_id' => $post->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function removePost(Post $post): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        try {
            $this->redis->delete($this->postDocuments->key($post));
        } catch (\Throwable) {
            // Ignore missing keys.
        }
    }

    public function indexAuthor(AuthorAlias $alias): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        $this->ensureIndexes();

        if (! $this->authorDocuments->isSearchable($alias)) {
            $this->redis->delete($this->authorDocuments->key($alias));

            return;
        }

        $this->redis->hashSet(
            $this->authorDocuments->key($alias),
            $this->authorDocuments->build($alias),
        );
    }

    public function ensureIndexes(): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        $this->redis->ensurePostsIndex(
            (string) config('search.indexes.posts'),
            $this->embeddings->semanticEnabled(),
        );

        $this->redis->ensureAuthorsIndex((string) config('search.indexes.authors'));
    }

    public function reindexAll(): void
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('RediSearch module is not available.');
        }

        $postsIndex = (string) config('search.indexes.posts');
        $authorsIndex = (string) config('search.indexes.authors');

        $this->redis->dropIndex($postsIndex);
        $this->redis->dropIndex($authorsIndex);

        $this->ensureIndexes();

        Post::query()
            ->with(['authorAlias', 'category', 'tags'])
            ->published()
            ->orderBy('id')
            ->chunkById(100, function ($posts): void {
                foreach ($posts as $post) {
                    $this->indexPost($post);
                }
            });

        AuthorAlias::query()
            ->active()
            ->listedInDirectory()
            ->orderBy('id')
            ->chunkById(100, function ($aliases): void {
                foreach ($aliases as $alias) {
                    $this->indexAuthor($alias);
                }
            });
    }

    /** @param  list<int>  $ids */
    private function hydratePosts(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $posts = Post::query()
            ->with(['user', 'authorAlias', 'category', 'tags'])
            ->published()
            ->visibleInFeeds()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->filter(fn (int $id) => $posts->has($id))
            ->map(fn (int $id) => $posts->get($id))
            ->values();
    }
}
