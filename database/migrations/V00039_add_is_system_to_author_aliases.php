<?php

use App\Models\AuthorAlias;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_aliases', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_primary');
        });

        AuthorAlias::withoutGlobalScopes()
            ->whereHas('user', fn ($query) => $query->withoutGlobalScope('tenant')->where('is_system', true))
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('author_aliases', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
