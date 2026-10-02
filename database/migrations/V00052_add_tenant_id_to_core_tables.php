<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'users',
        'author_aliases',
        'posts',
        'categories',
        'tags',
        'magazines',
        'category_requests',
        'category_redirects',
        'post_redirects',
        'registration_invites',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreignId('tenant_id')
                    ->default(1)
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            });

            DB::table($table)->update(['tenant_id' => 1]);
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->default(1)->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('site_settings')->update(['tenant_id' => 1]);

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropUnique(['key']);
            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'key']);
            $table->unique(['key']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
