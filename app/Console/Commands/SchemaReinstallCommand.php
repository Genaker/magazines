<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Support\Facades\Artisan;

class SchemaReinstallCommand extends Command
{
    use ConfirmableTrait;
    use Prohibitable;

    protected $signature = 'schema:reinstall
                            {--seed : Run DatabaseSeeder after migrate:fresh}
                            {--force : Run without confirmation (required in production)}';

    protected $description = 'Drop all tables and re-run V-prefixed schema migrations from scratch';

    public function handle(): int
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed('This will drop every table and rebuild the schema.')) {
            return self::FAILURE;
        }

        $this->components->info('Rebuilding schema (migrate:fresh)...');

        $exit = Artisan::call('migrate:fresh', [
            '--force' => true,
            '--no-interaction' => true,
        ], $this->output);

        if ($exit !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->option('seed')) {
            $this->components->info('Seeding database...');
            $exit = Artisan::call('db:seed', [
                '--force' => true,
                '--no-interaction' => true,
            ], $this->output);

            if ($exit !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        Artisan::call('schema:version', [], $this->output);

        $this->components->success('Schema reinstalled.');

        return self::SUCCESS;
    }
}
