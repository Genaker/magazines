<?php

namespace App\Support;

use App\Enums\PostStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** pg_search (ParadeDB) BM25 full-text search helpers. */
class PgSearchSupport
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

        return filter_var(config('search.postgres.pg_search.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function extensionInstalled(): bool
    {
        if (! self::driverIsPostgres()) {
            return false;
        }

        try {
            $result = DB::selectOne("SELECT 1 AS ok FROM pg_extension WHERE extname = 'pg_search' LIMIT 1");

            return $result !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function isReady(): bool
    {
        return self::configured()
            && self::extensionInstalled()
            && self::indexExists(self::postsIndexName());
    }

    public static function postsIndexName(): string
    {
        return (string) config('search.postgres.pg_search.indexes.posts', 'posts_bm25_idx');
    }

    public static function authorsIndexName(): string
    {
        return (string) config('search.postgres.pg_search.indexes.authors', 'author_aliases_bm25_idx');
    }

    public static function magazinesIndexName(): string
    {
        return (string) config('search.postgres.pg_search.indexes.magazines', 'magazines_bm25_idx');
    }

    /** @return '|||'|'&&&' */
    public static function matchOperator(): string
    {
        return (string) config('search.postgres.pg_search.match', 'any') === 'all' ? '&&&' : '|||';
    }

    public static function matchExpression(string $column, float $boost = 1.0): string
    {
        $operator = self::matchOperator();

        if ($boost === 1.0) {
            return "({$column} {$operator} ?)";
        }

        return "({$column} {$operator} ?::pdb.boost({$boost}))";
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

    public static function ensureExtension(): void
    {
        if (! self::driverIsPostgres()) {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_search');
    }

    public static function ensureIndexes(): void
    {
        if (! self::configured() || ! self::driverIsPostgres()) {
            return;
        }

        self::installIndexes();
    }

    /** Create extension and BM25 indexes (used by migrations). */
    public static function installIndexes(): void
    {
        if (! self::driverIsPostgres()) {
            return;
        }

        self::ensureExtension();

        if (! self::extensionInstalled()) {
            throw new \RuntimeException(
                'pg_search extension is not installed. Use the paradedb/paradedb PostgreSQL image or install pg_search manually.',
            );
        }

        $published = PostStatus::Published->value;

        if (! self::indexExists(self::postsIndexName())) {
            $index = self::postsIndexName();

            DB::statement("
                CREATE INDEX {$index} ON posts
                USING bm25 (id, title, subtitle, body)
                WITH (key_field='id')
                WHERE status = '{$published}'
                  AND published_at IS NOT NULL
                  AND feed_hidden_at IS NULL
                  AND deleted_at IS NULL
            ");
        }

        if (! self::indexExists(self::authorsIndexName())) {
            $index = self::authorsIndexName();

            DB::statement("
                CREATE INDEX {$index} ON author_aliases
                USING bm25 (id, name, username, bio)
                WITH (key_field='id')
                WHERE deleted_at IS NULL
            ");
        }

        if (Schema::hasTable('magazines') && ! self::indexExists(self::magazinesIndexName())) {
            $index = self::magazinesIndexName();

            DB::statement("
                CREATE INDEX {$index} ON magazines
                USING bm25 (id, name, description, slug)
                WITH (key_field='id')
            ");
        }
    }
}
