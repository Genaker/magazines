<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_lists', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('is_default');
        });

        DB::table('reading_lists')->where('is_default', true)->update(['is_private' => true]);
    }

    public function down(): void
    {
        Schema::table('reading_lists', function (Blueprint $table) {
            $table->dropColumn('is_private');
        });
    }
};
