<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->index(['viewed_on', 'post_id'], 'post_views_viewed_on_post_id_index');
            $table->index(['created_at', 'post_id'], 'post_views_created_at_post_id_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index(['author_alias_id', 'status', 'published_at'], 'posts_alias_status_published_index');
            $table->index(['category_id', 'status', 'published_at'], 'posts_category_status_published_index');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_category_status_published_index');
            $table->dropIndex('posts_alias_status_published_index');
        });

        Schema::table('post_views', function (Blueprint $table) {
            $table->dropIndex('post_views_created_at_post_id_index');
            $table->dropIndex('post_views_viewed_on_post_id_index');
        });
    }
};
