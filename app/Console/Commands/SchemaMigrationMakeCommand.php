<?php

namespace App\Console\Commands;

use App\Support\SchemaMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SchemaMigrationMakeCommand extends Command
{
    protected $signature = 'schema:make
                            {name : Short snake_case description (e.g. add_widget_table)}';

    protected $description = 'Create a version-prefixed schema migration (V00001_name.php)';

    public function handle(): int
    {
        $version = SchemaMigration::nextVersion();
        $filename = SchemaMigration::formatFilename($version, (string) $this->argument('name'));
        $path = SchemaMigration::migrationsPath().'/'.$filename;

        if (File::exists($path)) {
            throw new RuntimeException("Migration already exists: {$filename}");
        }

        $stub = File::get(base_path('stubs/schema-migration.stub'));
        File::put($path, $stub);

        $this->components->info('Schema migration created.');
        $this->line("  <fg=gray>version</>  ".SchemaMigration::displayVersion($version));
        $this->line("  <fg=gray>file</>     database/migrations/{$filename}");
        $this->newLine();
        $this->line('Run: php artisan migrate');

        return self::SUCCESS;
    }
}
