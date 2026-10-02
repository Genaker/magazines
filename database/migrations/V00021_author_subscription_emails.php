<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_subscriptions', function (Blueprint $table) {
            $table->string('email_delivery', 16)->default('instant')->after('author_id');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('subscription_notified_at')->nullable()->after('published_at');
        });

        Schema::create('subscription_digest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subscriber_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_digest_items');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('subscription_notified_at');
        });

        Schema::table('author_subscriptions', function (Blueprint $table) {
            $table->dropColumn('email_delivery');
        });
    }
};
