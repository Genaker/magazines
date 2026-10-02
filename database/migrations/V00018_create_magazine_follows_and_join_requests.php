<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazine_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('magazine_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'magazine_id']);
        });

        Schema::create('magazine_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magazine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['magazine_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazine_join_requests');
        Schema::dropIfExists('magazine_user');
    }
};
