<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            \App\Support\PgSearchSupport::installIndexes();
        } catch (\Throwable) {
            // pg_search unavailable (plain Postgres image) — tsvector fallback remains.
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $indexes = [
            \App\Support\PgSearchSupport::postsIndexName(),
            \App\Support\PgSearchSupport::authorsIndexName(),
            \App\Support\PgSearchSupport::magazinesIndexName(),
        ];

        foreach ($indexes as $index) {
            \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
