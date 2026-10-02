<?php

namespace App\Console\Commands;

use App\Services\Search\RedisSearchDriver;
use Illuminate\Console\Command;

class SearchEnsureIndexesCommand extends Command
{
    protected $signature = 'search:ensure-indexes';

    protected $description = 'Create RediSearch indexes when missing';

    public function handle(RedisSearchDriver $driver): int
    {
        if (! $driver->isAvailable()) {
            $this->error('RediSearch is not available on the configured Redis connection.');

            return self::FAILURE;
        }

        $driver->ensureIndexes();

        $this->info('Search indexes are ready.');

        return self::SUCCESS;
    }
}
