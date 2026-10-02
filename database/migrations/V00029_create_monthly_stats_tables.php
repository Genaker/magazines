<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_monthly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->unsignedInteger('followers_gained')->default(0);
            $table->unsignedInteger('subscribers_gained')->default(0);
            $table->unsignedInteger('followers_total')->default(0);
            $table->unsignedInteger('subscribers_total')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year', 'month']);
        });

        Schema::create('post_monthly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->timestamps();

            $table->unique(['post_id', 'year', 'month']);
            $table->index(['user_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_monthly_stats');
        Schema::dropIfExists('author_monthly_stats');
    }
};
