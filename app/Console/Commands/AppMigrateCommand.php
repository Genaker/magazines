<?php

namespace App\Console\Commands;

use App\Support\AppInstaller;
use App\Support\DatabaseMigrator;
use Illuminate\Console\Command;

class AppMigrateCommand extends Command
{
    protected $signature = 'app:migrate';

    protected $description = 'Run pending migrations in-process (no migrate Artisan subprocess)';

    public function handle(DatabaseMigrator $migrator, AppInstaller $installer): int
    {
        if (! $installer->testDatabaseConnection()['ok']) {
            $this->components->error('Database connection failed.');

            return self::FAILURE;
        }

        $ran = $migrator->run();

        if ($ran === []) {
            $this->components->info('Nothing to migrate.');

            return self::SUCCESS;
        }

        $this->components->success('Migrated: '.count($ran).' file(s).');
        foreach ($ran as $file) {
            $this->line("  {$file}");
        }

        return self::SUCCESS;
    }
}
