<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->json('logo_variants')->nullable();
            $table->timestamps();
        });

        Schema::create('magazine_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magazine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['magazine_id', 'user_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('magazine_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->string('magazine_submission_status')->nullable()->after('magazine_id');
            $table->json('cover_variants')->nullable()->after('cover_image');
            $table->unsignedInteger('autosave_revision')->default(0)->after('likes_count');
        });

        Schema::create('post_autosave_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
        });

        Schema::create('login_links', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_links');
        Schema::dropIfExists('post_autosave_snapshots');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magazine_id');
            $table->dropColumn(['magazine_submission_status', 'cover_variants', 'autosave_revision']);
        });

        Schema::dropIfExists('magazine_members');
        Schema::dropIfExists('magazines');
    }
};
