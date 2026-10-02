<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('type', 20)->default('article')->after('slug');
        });

        Schema::create('post_gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->json('variants')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('caption')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_gallery_items');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
