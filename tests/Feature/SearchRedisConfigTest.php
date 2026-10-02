<?php

namespace Tests\Feature;

use App\Support\SearchRedis;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SearchRedisConfigTest extends TestCase
{
    public function test_search_redis_connection_defaults_to_search_name(): void
    {
        Config::set('search.redis.connection', 'search');

        $this->assertSame('search', config('search.redis.connection'));
    }

    public function test_search_redis_uses_dedicated_host_when_configured(): void
    {
        $this->assertSame(
            env('REDIS_SEARCH_HOST', env('REDIS_HOST')),
            config('database.redis.search.host'),
        );
    }

    public function test_search_redis_helper_reports_split_hosts(): void
    {
        Config::set('database.redis.default.host', 'cache.example');
        Config::set('database.redis.search.host', 'search.example');

        $this->assertTrue(SearchRedis::usesDedicatedHost());

        Config::set('database.redis.search.host', 'cache.example');

        $this->assertFalse(SearchRedis::usesDedicatedHost());
    }
}
