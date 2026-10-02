<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('magazine_id')
                ->nullable()
                ->after('parent_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::create('category_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_redirects');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magazine_id');
        });
    }
};
