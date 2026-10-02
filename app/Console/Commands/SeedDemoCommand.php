<?php

namespace App\Console\Commands;

use App\Support\AppInstaller;
use App\Support\DatabaseMigrator;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedDemoCommand extends Command
{
    protected $signature = 'app:seed-demo {--force : Seed even when users already exist}';

    protected $description = 'Load demo categories, posts, magazine, and sample users (DatabaseSeeder)';

    public function handle(AppInstaller $installer, DatabaseMigrator $migrator): int
    {
        if (! $installer->testDatabaseConnection()['ok']) {
            $this->components->error('Database connection failed.');

            return self::FAILURE;
        }

        $migrator->run();

        if ($installer->hasUsers() && ! $this->option('force')) {
            $this->components->error('Users already exist. Use --force to run the demo seeder anyway (may fail on duplicates).');

            return self::FAILURE;
        }

        $this->components->info('Seeding demo data…');

        Artisan::call('db:seed', ['--force' => true], $this->output);

        $this->components->success('Demo data seeded.');
        $this->line('  Super admin: admin@magazines.test / password');
        $this->line('  Demo author: '.DemoContentSeeder::DEMO_AUTHOR_EMAIL.' / '.DemoContentSeeder::DEMO_AUTHOR_PASSWORD);

        return self::SUCCESS;
    }
}
