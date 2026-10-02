<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (\Throwable) {
            return;
        }

        $column = (string) config('search.postgres.pgvector.column', 'search_embedding');
        $dimensions = (int) config('search.embeddings.dimensions', 768);

        if (! Schema::hasColumn('posts', $column)) {
            DB::statement("ALTER TABLE posts ADD COLUMN {$column} vector({$dimensions}) NULL");
        }

        $index = (string) config('search.postgres.pgvector.index', 'posts_search_embedding_idx');

        $exists = DB::selectOne(
            'SELECT 1 AS ok FROM pg_indexes WHERE schemaname = current_schema() AND indexname = ? LIMIT 1',
            [$index],
        );

        if ($exists === null) {
            DB::statement(
                "CREATE INDEX {$index} ON posts USING hnsw ({$column} vector_cosine_ops)",
            );
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $column = (string) config('search.postgres.pgvector.column', 'search_embedding');
        $index = (string) config('search.postgres.pgvector.index', 'posts_search_embedding_idx');

        DB::statement("DROP INDEX IF EXISTS {$index}");

        if (Schema::hasColumn('posts', $column)) {
            DB::statement("ALTER TABLE posts DROP COLUMN {$column}");
        }
    }
};
