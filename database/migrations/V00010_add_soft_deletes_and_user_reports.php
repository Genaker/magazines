<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->unique(['reporter_id', 'reported_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reports');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
