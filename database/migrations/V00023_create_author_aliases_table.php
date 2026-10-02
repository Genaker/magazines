<?php

use App\Models\AuthorAlias;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('author_aliases')) {
            Schema::create('author_aliases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('username', 50);
                $table->string('name');
                $table->text('bio')->nullable();
                $table->string('avatar')->nullable();
                $table->string('website')->nullable();
                $table->string('twitter_handle')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('username');
                $table->index(['user_id', 'is_primary']);
            });
        }

        if (! Schema::hasColumn('posts', 'author_alias_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->foreignId('author_alias_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            });
        }

        if (AuthorAlias::withoutGlobalScopes()->doesntExist() && User::withoutGlobalScope('tenant')->exists()) {
            User::withoutGlobalScope('tenant')->withTrashed()->each(function (User $user): void {
                AuthorAlias::withoutGlobalScopes()->create([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'bio' => $user->bio,
                    'avatar' => $user->avatar,
                    'website' => $user->website,
                    'twitter_handle' => $user->twitter_handle,
                    'is_primary' => true,
                ]);
            });
        }

        if (Post::withoutGlobalScope('tenant')->whereNull('author_alias_id')->exists()) {
            Post::withoutGlobalScope('tenant')->withTrashed()->each(function (Post $post): void {
                $aliasId = AuthorAlias::withoutGlobalScopes()
                    ->where('user_id', $post->user_id)
                    ->where('is_primary', true)
                    ->value('id');

                if ($aliasId) {
                    $post->update(['author_alias_id' => $aliasId]);
                }
            });
        }

        if ($this->hasIndex('posts', 'posts_user_id_slug_unique')) {
            if (! $this->hasIndex('posts', 'posts_user_id_lookup_index')) {
                Schema::table('posts', function (Blueprint $table) {
                    $table->index('user_id', 'posts_user_id_lookup_index');
                });
            }

            Schema::table('posts', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'slug']);
            });
        }

        if (! $this->hasIndex('posts', 'posts_author_alias_id_slug_unique')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->unique(['author_alias_id', 'slug']);
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('posts', 'posts_author_alias_id_slug_unique')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropUnique(['author_alias_id', 'slug']);
            });
        }

        if (! $this->hasIndex('posts', 'posts_user_id_slug_unique')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->unique(['user_id', 'slug']);
            });
        }

        if (Schema::hasColumn('posts', 'author_alias_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('author_alias_id');
            });
        }

        Schema::dropIfExists('author_aliases');
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $row): bool => $row->name === $indexName);
        }

        if ($driver === 'pgsql') {
            return collect(DB::select(
                'select 1 from pg_indexes where schemaname = current_schema() and tablename = ? and indexname = ?',
                [$table, $indexName],
            ))->isNotEmpty();
        }

        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn (object $row): bool => $row->Key_name === $indexName);
    }
};
