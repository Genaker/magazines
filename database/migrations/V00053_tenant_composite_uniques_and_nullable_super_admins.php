<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->indexExists('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['email']);
            });
        }

        if ($this->indexExists('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['username']);
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable()->default(1)->change();
        });

        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['tenant_id' => null]);

        if (! $this->indexExists('users', 'users_tenant_id_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'email']);
            });
        }

        if (! $this->indexExists('users', 'users_tenant_id_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'username']);
            });
        }

        if ($this->indexExists('author_aliases', 'author_aliases_username_unique')) {
            Schema::table('author_aliases', function (Blueprint $table): void {
                $table->dropUnique(['username']);
            });
        }

        if (! $this->indexExists('author_aliases', 'author_aliases_tenant_id_username_unique')) {
            Schema::table('author_aliases', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'username']);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('author_aliases', 'author_aliases_tenant_id_username_unique')) {
            Schema::table('author_aliases', function (Blueprint $table): void {
                $table->dropUnique(['tenant_id', 'username']);
            });
        }

        if (! $this->indexExists('author_aliases', 'author_aliases_username_unique')) {
            Schema::table('author_aliases', function (Blueprint $table): void {
                $table->unique(['username']);
            });
        }

        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['tenant_id' => 1]);

        if ($this->indexExists('users', 'users_tenant_id_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['tenant_id', 'email']);
            });
        }

        if ($this->indexExists('users', 'users_tenant_id_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['tenant_id', 'username']);
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->default(1)->nullable(false)->change();
        });

        if (! $this->indexExists('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique(['email']);
            });
        }

        if (! $this->indexExists('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique(['username']);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return count(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index])) > 0;
    }
};
