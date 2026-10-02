<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_links', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('login_links', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
