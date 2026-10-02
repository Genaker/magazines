<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazines', function (Blueprint $table) {
            $table->timestamp('nav_pinned_at')->nullable()->after('custom_fields');
            $table->unsignedSmallInteger('nav_sort_order')->default(0)->after('nav_pinned_at');
        });
    }

    public function down(): void
    {
        Schema::table('magazines', function (Blueprint $table) {
            $table->dropColumn(['nav_pinned_at', 'nav_sort_order']);
        });
    }
};
