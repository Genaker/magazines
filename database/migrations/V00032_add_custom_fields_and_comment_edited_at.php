<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('pinned_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('allow_comments');
        });

        Schema::table('magazines', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('logo_variants');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::table('magazines', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });
    }
};
