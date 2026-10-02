<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Idempotent fix when V00043 was recorded but password stayed NOT NULL (e.g. older deploy). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'password')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if ($this->passwordColumnIsNotNull()) {
                DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
            }

            return;
        }

        if ($driver === 'pgsql' && $this->passwordColumnIsNotNull()) {
            DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');
        }
    }

    public function down(): void
    {
        // V00043 down reverts the column constraint.
    }

    private function passwordColumnIsNotNull(): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $column = DB::selectOne(
                'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [Schema::getConnection()->getDatabaseName(), 'users', 'password']
            );

            return $column !== null && $column->IS_NULLABLE === 'NO';
        }

        if ($driver === 'pgsql') {
            $column = DB::selectOne(
                'SELECT is_nullable FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                ['users', 'password']
            );

            return $column !== null && strtoupper((string) $column->is_nullable) === 'NO';
        }

        return false;
    }
};
