<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('social_links')->nullable()->after('twitter_handle');
        });

        Schema::table('author_aliases', function (Blueprint $table) {
            $table->json('social_links')->nullable()->after('twitter_handle');
        });
    }

    public function down(): void
    {
        Schema::table('author_aliases', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });
    }
};
