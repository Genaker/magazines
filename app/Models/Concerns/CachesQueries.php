<?php

namespace App\Models\Concerns;

use App\Support\EntityCache;
use App\Support\FreshPostCounts;
use Closure;

/**
 * Wraps Laravel's cache repository for model queries.
 * Laravel Eloquent has no built-in query cache; Cache::remember is the native approach.
 */
trait CachesQueries
{
    protected static function cacheTag(): ?string
    {
        $meta = config('entity-cache.models.'.class_basename(static::class), []);

        return $meta['tag'] ?? null;
    }

    protected static function cacheTtl(): int
    {
        $meta = config('entity-cache.models.'.class_basename(static::class), []);
        $ttlKey = $meta['ttl_key'] ?? 'posts';

        return (int) config("entity-cache.ttl.{$ttlKey}", 600);
    }

    protected static function rememberQuery(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        $ttl ??= static::cacheTtl();

        return static::afterRememberQuery(
            EntityCache::remember($key, $ttl, $callback, static::cacheTag()),
        );
    }

    protected static function rememberQueryForever(string $key, Closure $callback): mixed
    {
        return static::afterRememberQuery(
            EntityCache::rememberForever($key, $callback, static::cacheTag()),
        );
    }

    protected static function afterRememberQuery(mixed $result): mixed
    {
        return FreshPostCounts::hydrate($result);
    }
}
