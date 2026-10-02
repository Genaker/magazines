<?php

namespace App\Console\Commands;

use App\Support\SchemaMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class SchemaVersionCommand extends Command
{
    protected $signature = 'schema:version';

    protected $description = 'Show latest V-prefixed schema version and pending migrations';

    public function handle(): int
    {
        $defined = SchemaMigration::latestDefinedVersion();
        $prefix = SchemaMigration::prefix();

        if ($defined === 0) {
            $this->line('Latest defined schema version: none (next: '.SchemaMigration::displayVersion(1).')');
        } else {
            $this->line('Latest defined schema version: '.SchemaMigration::displayVersion($defined));
        }

        if (! SchemaFacade::hasTable('migrations')) {
            $this->components->warn('Migrations table not found (database not migrated yet).');

            return self::SUCCESS;
        }

        $applied = DB::table('migrations')
            ->where('migration', 'like', $prefix.'%')
            ->orderBy('migration')
            ->pluck('migration')
            ->all();

        if ($applied === []) {
            $this->line('Applied V migrations: none');
        } else {
            $this->line('Applied V migrations: '.count($applied));
            foreach ($applied as $name) {
                $this->line("  {$name}");
            }
        }

        $pending = collect(glob(SchemaMigration::migrationsPath().'/'.$prefix.'*.php') ?: [])
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
            ->diff($applied)
            ->sort()
            ->values();

        if ($pending->isEmpty()) {
            $this->components->info('No pending V migrations.');
        } else {
            $this->components->warn('Pending V migrations: '.$pending->count());
            foreach ($pending as $name) {
                $this->line("  {$name}");
            }
        }

        return self::SUCCESS;
    }
}
