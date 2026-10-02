<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->fullText(['title', 'subtitle', 'body']);
        });

        Schema::table('author_aliases', function (Blueprint $table) {
            $table->fullText(['name', 'username', 'bio']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->fullText(['name']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->fullText(['name', 'slug']);
        });

        Schema::table('magazines', function (Blueprint $table) {
            $table->fullText(['name', 'description', 'slug']);
        });
    }

    public function down(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropFullText(['title', 'subtitle', 'body']);
        });

        Schema::table('author_aliases', function (Blueprint $table) {
            $table->dropFullText(['name', 'username', 'bio']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropFullText(['name']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropFullText(['name', 'slug']);
        });

        Schema::table('magazines', function (Blueprint $table) {
            $table->dropFullText(['name', 'description', 'slug']);
        });
    }

    private function supportsFullText(): bool
    {
        return in_array(
            Schema::getConnection()->getDriverName(),
            ['mysql', 'mariadb', 'pgsql'],
            true,
        );
    }
};
