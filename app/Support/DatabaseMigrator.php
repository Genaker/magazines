<?php

namespace App\Support;

use Illuminate\Database\Migrations\Migrator;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;
use Throwable;

/** Run pending migrations in-process (no shell, no Artisan command). */
class DatabaseMigrator
{
    public function __construct(private Migrator $migrator) {}

    /**
     * Apply all pending migration files.
     *
     * @param  list<string>|null  $paths  Defaults to database/migrations
     * @return list<string> Basenames of migration files that ran
     */
    public function run(?string $connection = null, ?array $paths = null): array
    {
        $connection ??= (string) config('database.default');
        $paths ??= [database_path('migrations')];

        try {
            return $this->migrator->usingConnection($connection, function () use ($connection, $paths): array {
                $this->prepareConnection($connection);
                $this->ensureRepository();
                $this->migrator->setOutput(new NullOutput());

                return $this->migrator->run($paths);
            });
        } catch (Throwable $e) {
            throw new RuntimeException('Database migration failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function repositoryExists(?string $connection = null): bool
    {
        $connection ??= (string) config('database.default');

        return $this->migrator->usingConnection($connection, function () use ($connection): bool {
            $this->prepareConnection($connection);

            return $this->migrator->repositoryExists();
        });
    }

    private function prepareConnection(string $connection): void
    {
        $this->migrator->setConnection($connection);
        $this->migrator->getRepository()->setSource($connection);
    }

    private function ensureRepository(): void
    {
        if (! $this->migrator->repositoryExists()) {
            $this->migrator->getRepository()->createRepository();
        }
    }
}
