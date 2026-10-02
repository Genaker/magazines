<?php

namespace App\Services\Search;

use App\Support\SearchRedis;
use App\Support\SearchTextPreprocessor;

/** Low-level RediSearch index and document operations. */
class RedisSearchClient
{
    public function __construct(
        private SearchPostContent $postContent,
        private SearchTextPreprocessor $preprocessor,
    ) {}
    public function moduleAvailable(): bool
    {
        try {
            $result = SearchRedis::command(['FT._LIST']);

            return is_array($result);
        } catch (\Throwable) {
            return false;
        }
    }

    public function indexExists(string $index): bool
    {
        try {
            $result = SearchRedis::command(['FT.INFO', $index]);

            return is_array($result) && $result !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    public function dropIndex(string $index): void
    {
        try {
            SearchRedis::command(['FT.DROPINDEX', $index, 'DD']);
        } catch (\Throwable) {
            // Index may not exist yet.
        }
    }

    public function ensurePostsIndex(string $index, bool $withVectors): void
    {
        if ($this->indexExists($index)) {
            return;
        }

        $prefix = config('search.redis.prefix', 'search:').'post:';
        $dimensions = (int) config('search.embeddings.dimensions', 768);
        $weights = config('search.post.fulltext.weights', []);

        $schema = [
            'post_id', 'NUMERIC', 'SORTABLE',
            'title', 'TEXT', 'WEIGHT', (string) ($weights['title'] ?? 5.0),
            'subtitle', 'TEXT', 'WEIGHT', (string) ($weights['subtitle'] ?? 3.0),
            'body_text', 'TEXT', 'WEIGHT', (string) ($weights['body'] ?? 1.0),
            'author_username', 'TAG',
            'author_name', 'TEXT', 'WEIGHT', (string) ($weights['author_name'] ?? 2.0),
            'category_name', 'TEXT', 'WEIGHT', (string) ($weights['category_name'] ?? 1.5),
            'category_id', 'NUMERIC',
            'category_slug', 'TAG',
            'tag_slugs', 'TAG', 'SEPARATOR', ',',
            'tag_names', 'TEXT',
            'magazine_id', 'NUMERIC',
            'published_at', 'NUMERIC', 'SORTABLE',
            'views_count', 'NUMERIC',
            'likes_count', 'NUMERIC',
            'feed_hidden', 'NUMERIC',
        ];

        if ($withVectors) {
            $schema = array_merge($schema, [
                'embedding', 'VECTOR', 'HNSW', '6',
                'TYPE', 'FLOAT32',
                'DIM', (string) $dimensions,
                'DISTANCE_METRIC', 'COSINE',
            ]);
        }

        $result = SearchRedis::command(array_merge(
            ['FT.CREATE', $index, 'ON', 'HASH', 'PREFIX', '1', $prefix, 'SCHEMA'],
            $schema,
        ));

        if ($result === false) {
            throw new \RuntimeException('RediSearch FT.CREATE failed. Use Redis Stack for SEARCH_DRIVER=redis.');
        }
    }

    public function ensureAuthorsIndex(string $index): void
    {
        if ($this->indexExists($index)) {
            return;
        }

        $prefix = config('search.redis.prefix', 'search:').'author:';

        $result = SearchRedis::command([
            'FT.CREATE', $index, 'ON', 'HASH', 'PREFIX', '1', $prefix, 'SCHEMA',
            'alias_id', 'NUMERIC', 'SORTABLE',
            'username', 'TAG',
            'name', 'TEXT', 'WEIGHT', '3.0',
            'bio', 'TEXT',
            'is_banned', 'NUMERIC',
        ]);

        if ($result === false) {
            throw new \RuntimeException('RediSearch FT.CREATE failed. Use Redis Stack for SEARCH_DRIVER=redis.');
        }
    }

    /** @param  array<string, string|int|float>  $fields */
    public function hashSet(string $key, array $fields): void
    {
        $flat = [];

        foreach ($fields as $field => $value) {
            $flat[] = $field;
            $flat[] = $value;
        }

        SearchRedis::command(array_merge(['HSET', $key], $flat));
    }

    public function delete(string $key): void
    {
        SearchRedis::command(['DEL', $key]);
    }

    /**
     * @return list<array{id: int, score: float}>
     */
    public function searchPosts(string $index, string $query, int $limit, ?string $vectorBlob = null): array
    {
        if ($vectorBlob !== null) {
            return $this->hybridSearch($index, $query, $limit, $vectorBlob);
        }

        return $this->parseResults($this->ftSearch($index, $query, $limit));
    }

    /**
     * @return list<array{id: int, score: float}>
     */
    public function knnPosts(string $index, string $vectorBlob, int $limit, ?int $excludePostId = null): array
    {
        $filter = '@feed_hidden:[0 0]';
        $k = max($limit + ($excludePostId ? 1 : 0), $limit);

        $query = "({$filter})=>[KNN {$k} @embedding \$vec AS vector_score]";

        $raw = SearchRedis::command([
            'FT.SEARCH', $index, $query,
            'PARAMS', '2', 'vec', $vectorBlob,
            'SORTBY', 'vector_score',
            'RETURN', '2', 'post_id', 'vector_score',
            'DIALECT', '2',
            'LIMIT', '0', (string) $limit,
        ]);

        $results = $this->parseResults($raw);

        if ($excludePostId) {
            $results = array_values(array_filter(
                $results,
                fn (array $row) => $row['id'] !== $excludePostId,
            ));
        }

        return array_slice($results, 0, $limit);
    }

    /** @return list<array{id: int, score: float}> */
    public function searchAuthors(string $index, string $query, int $limit): array
    {
        $escaped = $this->escapeQuery($query);

        if ($escaped === '') {
            return [];
        }

        $redisQuery = "(@is_banned:[0 0]) (@name:{$escaped} | @username:{$escaped} | @bio:{$escaped})";

        return $this->parseResults($this->ftSearch($index, $redisQuery, $limit, ['alias_id']));
    }

    /** @return list<array{id: int, score: float}> */
    private function hybridSearch(string $index, string $query, int $limit, string $vectorBlob): array
    {
        $textResults = $this->searchPosts($index, $query, $limit, null);
        $vectorResults = $this->knnPosts($index, $vectorBlob, $limit);

        $textWeight = (float) config('search.semantic.hybrid_text_weight', 0.4);
        $vectorWeight = (float) config('search.semantic.hybrid_vector_weight', 0.6);

        $scores = [];

        foreach ($textResults as $i => $row) {
            $scores[$row['id']] = ($scores[$row['id']] ?? 0) + $textWeight * (1 / (1 + $i));
        }

        foreach ($vectorResults as $i => $row) {
            $scores[$row['id']] = ($scores[$row['id']] ?? 0) + $vectorWeight * (1 / (1 + $i));
        }

        arsort($scores);

        return collect($scores)
            ->take($limit)
            ->map(fn (float $score, int $id) => ['id' => $id, 'score' => $score])
            ->values()
            ->all();
    }

    /** @param  list<string>  $returnFields */
    private function ftSearch(string $index, string $query, int $limit, array $returnFields = ['post_id']): mixed
    {
        $args = ['FT.SEARCH', $index, $query, 'DIALECT', '2', 'LIMIT', '0', (string) $limit];

        if ($returnFields !== []) {
            $args[] = 'RETURN';
            $args[] = (string) count($returnFields);
            foreach ($returnFields as $field) {
                $args[] = $field;
            }
        }

        return SearchRedis::command($args);
    }

    /** @return list<array{id: int, score: float}> */
    private function parseResults(mixed $raw): array
    {
        if (! is_array($raw) || count($raw) < 1) {
            return [];
        }

        $results = [];
        $count = (int) $raw[0];

        for ($i = 1; $i < count($raw); $i += 2) {
            $fields = $raw[$i + 1] ?? [];
            $id = null;
            $score = 1.0;

            if (is_array($fields)) {
                for ($f = 0; $f < count($fields); $f += 2) {
                    $name = $fields[$f] ?? null;
                    $value = $fields[$f + 1] ?? null;

                    if ($name === 'post_id' || $name === 'alias_id') {
                        $id = (int) $value;
                    }

                    if ($name === 'vector_score') {
                        $score = (float) $value;
                    }
                }
            }

            if ($id !== null) {
                $results[] = ['id' => $id, 'score' => $score];
            }
        }

        return $results;
    }

    public function escapeQuery(string $query): string
    {
        $query = $this->preprocessor->forFullTextQuery($query);

        if ($query === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $query) ?: [];
        $escaped = [];

        foreach ($parts as $part) {
            $part = preg_replace('/([\\@\\{\\}\\|\\-\\\"\\\'\\:\\/\\(\\)\\[\\]\\~\\*\\?\\\\])/', '\\\\$1', $part) ?? $part;
            $escaped[] = $part.'*';
        }

        return implode(' | ', $escaped);
    }

    public function buildPostQuery(string $query): string
    {
        $escaped = $this->escapeQuery($query);

        if ($escaped === '') {
            return '';
        }

        $clauses = $this->postContent->fullTextQueryFieldClauses($escaped);

        if ($clauses === []) {
            return '';
        }

        return '(@feed_hidden:[0 0]) ('.implode(' | ', $clauses).')';
    }
}
