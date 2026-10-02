<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** pgvector extension helpers for PostgreSQL semantic search. */
class PgVectorSearch
{
    public static function driverIsPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }

    public static function configured(): bool
    {
        if (! self::driverIsPostgres()) {
            return false;
        }

        return filter_var(config('search.postgres.pgvector.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function extensionInstalled(): bool
    {
        if (! self::driverIsPostgres()) {
            return false;
        }

        try {
            $result = DB::selectOne("SELECT 1 AS ok FROM pg_extension WHERE extname = 'vector' LIMIT 1");

            return $result !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function isReady(): bool
    {
        return self::configured() && self::extensionInstalled();
    }

    public static function column(): string
    {
        return (string) config('search.postgres.pgvector.column', 'search_embedding');
    }

    public static function indexName(): string
    {
        return (string) config('search.postgres.pgvector.index', 'posts_search_embedding_idx');
    }

    public static function indexExists(string $indexName): bool
    {
        try {
            $result = DB::selectOne(
                'SELECT 1 AS ok FROM pg_indexes WHERE schemaname = current_schema() AND indexname = ? LIMIT 1',
                [$indexName],
            );

            return $result !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @param  list<float>  $values */
    public static function toLiteral(array $values): string
    {
        return '['.implode(',', array_map(static fn (float $value): string => (string) $value, $values)).']';
    }

    /**
     * @param  list<int>  $textIds
     * @param  list<int>  $vectorIds
     * @return list<int>
     */
    public static function mergeHybridResults(array $textIds, array $vectorIds, int $limit): array
    {
        $textWeight = (float) config('search.semantic.hybrid_text_weight', 0.4);
        $vectorWeight = (float) config('search.semantic.hybrid_vector_weight', 0.6);
        $scores = [];

        foreach ($textIds as $i => $id) {
            $scores[$id] = ($scores[$id] ?? 0) + $textWeight * (1 / (1 + $i));
        }

        foreach ($vectorIds as $i => $id) {
            $scores[$id] = ($scores[$id] ?? 0) + $vectorWeight * (1 / (1 + $i));
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, $limit);
    }
}
