<?php

namespace App\Support;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;

/** Redis connection for RediSearch (may differ from cache/session Redis). */
class SearchRedis
{
    public static function connection(): Connection
    {
        $name = (string) config('search.redis.connection', 'search');

        return app(RedisFactory::class)->connection($name);
    }

    public static function usesDedicatedHost(): bool
    {
        $searchHost = (string) config('database.redis.search.host');
        $defaultHost = (string) config('database.redis.default.host');

        return $searchHost !== $defaultHost;
    }

    /** @param  list<mixed>  $arguments */
    public static function command(array $arguments): mixed
    {
        $connection = static::connection();
        $client = $connection->client();

        if (method_exists($client, 'rawCommand')) {
            return $client->rawCommand(...$arguments);
        }

        if (method_exists($connection, 'executeRaw')) {
            return $connection->executeRaw($arguments);
        }

        throw new \RuntimeException('Redis client does not support raw RediSearch commands.');
    }
}
