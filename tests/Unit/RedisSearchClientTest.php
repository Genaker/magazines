<?php

namespace Tests\Unit;

use App\Services\Search\RedisSearchClient;
use Tests\TestCase;

class RedisSearchClientTest extends TestCase
{
    public function test_module_available_is_false_when_ft_list_does_not_return_array(): void
    {
        if (extension_loaded('redis') && app(RedisSearchClient::class)->moduleAvailable()) {
            $this->markTestSkipped('RediSearch is available in this environment.');
        }

        $this->assertFalse(app(RedisSearchClient::class)->moduleAvailable());
    }
}
