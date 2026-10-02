<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('reading_list_post', function (Blueprint $table) {
            $table->foreignId('reading_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['reading_list_id', 'post_id']);
        });

        if (Schema::hasTable('bookmarks')) {
            $bookmarks = DB::table('bookmarks')->orderBy('id')->get();
            $listsByUser = [];

            foreach ($bookmarks as $bookmark) {
                if (! isset($listsByUser[$bookmark->user_id])) {
                    $listsByUser[$bookmark->user_id] = DB::table('reading_lists')->insertGetId([
                        'user_id' => $bookmark->user_id,
                        'name' => 'Reading list',
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('reading_list_post')->insertOrIgnore([
                    'reading_list_id' => $listsByUser[$bookmark->user_id],
                    'post_id' => $bookmark->post_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::dropIfExists('bookmarks');
        }
    }

    public function down(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
        });

        $items = DB::table('reading_list_post')
            ->join('reading_lists', 'reading_lists.id', '=', 'reading_list_post.reading_list_id')
            ->select('reading_lists.user_id', 'reading_list_post.post_id', 'reading_list_post.created_at', 'reading_list_post.updated_at')
            ->get();

        foreach ($items as $item) {
            DB::table('bookmarks')->insertOrIgnore([
                'user_id' => $item->user_id,
                'post_id' => $item->post_id,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);
        }

        Schema::dropIfExists('reading_list_post');
        Schema::dropIfExists('reading_lists');
    }
};
