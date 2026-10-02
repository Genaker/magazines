<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_moderators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'user_id']);
        });

        Schema::create('tag_moderators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tag_id', 'user_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('feed_hidden_at')->nullable()->after('pinned_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('feed_hidden_at');
        });

        Schema::dropIfExists('tag_moderators');
        Schema::dropIfExists('category_moderators');
    }
};
