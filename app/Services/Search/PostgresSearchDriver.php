<?php

namespace App\Services\Search;

use App\Contracts\Search\SearchDriver;
use App\Models\AuthorAlias;
use App\Models\Post;
use App\Services\Embeddings\EmbeddingService;
use App\Support\PgSearchSupport;
use App\Support\PgVectorSearch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/** PostgreSQL search: pg_search BM25, optional pgvector hybrid. */
class PostgresSearchDriver implements SearchDriver
{
    public function __construct(
        private DatabaseSearchDriver $database,
        private PgSearchQueries $pgSearch,
        private PostSearchDocumentBuilder $postDocuments,
        private EmbeddingService $embeddings,
        private RecommendationService $recommendations,
    ) {}

    public function isAvailable(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }

    public function searchPosts(string $query, int $limit = 20): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        if (PgSearchSupport::isReady()) {
            $textIds = $this->pgSearch->searchPostIds($query, $limit * 2);
        } else {
            return $this->database->searchPosts($query, $limit);
        }

        if (! $this->vectorsActive()) {
            return $this->hydratePosts(array_slice($textIds, 0, $limit));
        }

        $vector = $this->embeddings->embedQuery($query);

        if ($vector === []) {
            return $this->hydratePosts(array_slice($textIds, 0, $limit));
        }

        $vectorIds = $this->knnPostIds($vector, $limit * 2);

        if ($vectorIds === []) {
            return $this->hydratePosts(array_slice($textIds, 0, $limit));
        }

        if ($textIds === []) {
            return $this->hydratePosts(array_slice($vectorIds, 0, $limit));
        }

        $mergedIds = PgVectorSearch::mergeHybridResults($textIds, $vectorIds, $limit);

        return $this->hydratePosts($mergedIds);
    }

    public function searchAuthors(string $query, int $limit = 10): Collection
    {
        if (PgSearchSupport::isReady()) {
            $ids = $this->pgSearch->searchAuthorIds($query, $limit);

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

        return $this->database->searchAuthors($query, $limit);
    }

    public function relatedPosts(Post $post, int $limit = 6): Collection
    {
        if (! $this->vectorsActive()) {
            return $this->recommendations->relatedByTaxonomy($post, $limit);
        }

        $vector = $this->embeddings->embedForIndex(
            $this->postDocuments->embeddingText($post),
        );

        if ($vector === []) {
            return $this->recommendations->relatedByTaxonomy($post, $limit);
        }

        $ids = $this->knnPostIds($vector, $limit + 1, $post->id);

        if ($ids === []) {
            return $this->recommendations->relatedByTaxonomy($post, $limit);
        }

        return $this->hydratePosts($ids);
    }

    public function indexPost(Post $post): void
    {
        if (! PgVectorSearch::isReady()) {
            return;
        }

        if (! $this->postDocuments->isSearchable($post)) {
            $this->removePost($post);

            return;
        }

        if (! $this->embeddings->semanticEnabled()) {
            $this->removePost($post);

            return;
        }

        try {
            $vector = $this->embeddings->embedForIndex(
                $this->postDocuments->embeddingText($post),
            );

            if ($vector === []) {
                $this->removePost($post);

                return;
            }

            $column = PgVectorSearch::column();

            DB::update(
                "UPDATE posts SET {$column} = ?::vector WHERE id = ?",
                [PgVectorSearch::toLiteral($vector), $post->id],
            );
        } catch (\Throwable $exception) {
            Log::warning('Failed to index post embedding for PostgreSQL search', [
                'post_id' => $post->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function removePost(Post $post): void
    {
        if (! PgVectorSearch::isReady()) {
            return;
        }

        $column = PgVectorSearch::column();

        DB::table('posts')->where('id', $post->id)->update([$column => null]);
    }

    public function indexAuthor(AuthorAlias $alias): void {}

    public function ensureIndexes(): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        if (PgSearchSupport::configured()) {
            PgSearchSupport::ensureIndexes();
        }

        if (! PgVectorSearch::configured()) {
            return;
        }

        if (! PgVectorSearch::extensionInstalled()) {
            throw new \RuntimeException(
                'pgvector extension is not installed. ParadeDB includes pgvector, or use pgvector/pgvector.',
            );
        }

        $index = PgVectorSearch::indexName();
        $column = PgVectorSearch::column();

        if (PgVectorSearch::indexExists($index)) {
            return;
        }

        DB::statement(
            "CREATE INDEX {$index} ON posts USING hnsw ({$column} vector_cosine_ops)",
        );
    }

    public function reindexAll(): void
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('PostgreSQL search requires DB_CONNECTION=pgsql.');
        }

        $this->ensureIndexes();

        if (! PgVectorSearch::isReady() || ! $this->embeddings->semanticEnabled()) {
            return;
        }

        Post::query()
            ->with(['authorAlias', 'category', 'tags'])
            ->published()
            ->orderBy('id')
            ->chunkById(100, function ($posts): void {
                foreach ($posts as $post) {
                    $this->indexPost($post);
                }
            });
    }

    /** @param  list<float>  $vector @return list<int> */
    private function knnPostIds(array $vector, int $limit, ?int $excludePostId = null): array
    {
        if (! PgVectorSearch::isReady() || $limit < 1) {
            return [];
        }

        $column = PgVectorSearch::column();
        $literal = PgVectorSearch::toLiteral($vector);
        $fetch = max($limit + ($excludePostId ? 1 : 0), $limit);

        $rows = Post::query()
            ->published()
            ->visibleInFeeds()
            ->whereNotNull($column)
            ->when($excludePostId, fn ($query) => $query->where('id', '!=', $excludePostId))
            ->orderByRaw("{$column} <=> ?::vector", [$literal])
            ->limit($fetch)
            ->pluck('id')
            ->all();

        return array_slice($rows, 0, $limit);
    }

    private function vectorsActive(): bool
    {
        return PgVectorSearch::isReady() && $this->embeddings->semanticEnabled();
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
