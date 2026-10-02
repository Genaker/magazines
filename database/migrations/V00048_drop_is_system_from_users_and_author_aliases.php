<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_aliases', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('role');
        });

        Schema::table('author_aliases', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_primary');
        });
    }
};
