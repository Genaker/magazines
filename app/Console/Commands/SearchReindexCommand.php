<?php

namespace App\Console\Commands;

use App\Services\Search\RedisSearchDriver;
use Illuminate\Console\Command;

class SearchReindexCommand extends Command
{
    protected $signature = 'search:reindex {--drop : Drop and recreate indexes before reindexing}';

    protected $description = 'Rebuild the RediSearch post and author indexes from MariaDB';

    public function handle(RedisSearchDriver $driver): int
    {
        if (! $driver->isAvailable()) {
            $this->error('RediSearch is not available on the configured Redis connection.');

            return self::FAILURE;
        }

        $this->info('Reindexing published posts and authors...');

        $driver->reindexAll();

        $this->info('Search reindex complete.');

        return self::SUCCESS;
    }
}
