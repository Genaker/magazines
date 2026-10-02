<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('magic_login_failed_attempts')->default(0)->after('remember_token');
            $table->timestamp('magic_login_locked_at')->nullable()->after('magic_login_failed_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['magic_login_failed_attempts', 'magic_login_locked_at']);
        });
    }
};
