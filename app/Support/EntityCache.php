<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/** Tagged entity cache with optional disable and Eloquent serialization. */
class EntityCache
{
    /** Whether entity caching is active (can be turned off via config for debugging). */
    public static function enabled(): bool
    {
        return (bool) config('entity-cache.enabled', true);
    }

    /** Return the configured cache repository instance. */
    public static function store(): Repository
    {
        return Cache::store(config('entity-cache.store'));
    }

    /** Whether the configured store supports cache tags (redis, memcached, dynamodb). */
    public static function supportsTags(): bool
    {
        return in_array(config('entity-cache.store'), ['redis', 'memcached', 'dynamodb'], true);
    }

    /**
     * Remember a value for the given TTL, encoding Eloquent models for storage.
     *
     * Executes the callback directly when caching is disabled.
     */
    public static function remember(string $key, int $ttl, Closure $callback, ?string $tag = null): mixed
    {
        if (! self::enabled()) {
            return $callback();
        }

        $repository = self::tagged($tag); // tag-scoped store when redis/memcached supports it

        // Serialize Eloquent models before writing; decode after read
        $value = $repository->remember($key, $ttl, fn () => CacheSerializer::encode($callback()));

        return CacheSerializer::decode($value);
    }

    /** Remember a value indefinitely until explicitly forgotten or the tag is flushed. */
    public static function rememberForever(string $key, Closure $callback, ?string $tag = null): mixed
    {
        if (! self::enabled()) {
            return $callback();
        }

        $repository = self::tagged($tag);

        $value = $repository->rememberForever($key, fn () => CacheSerializer::encode($callback())); // encoded payload

        return CacheSerializer::decode($value);
    }

    /** Remove a single key from the entity cache store. */
    public static function forget(string $key): void
    {
        self::store()->forget($key);
    }

    /** Flush all keys under a tag, or the entire store when tags are unsupported. */
    public static function flushTag(string $tag): void
    {
        if (self::supportsTags()) {
            self::store()->tags($tag)->flush();

            return;
        }

        self::flushStore();
    }

    /** Flush the entire entity cache store (used when tags are unsupported). */
    public static function flushStore(): void
    {
        self::store()->flush();
    }

    /**
     * Flush each tag in sequence.
     *
     * @param list<string> $tags
     */
    public static function flushTags(array $tags): void
    {
        foreach ($tags as $tag) {
            self::flushTag($tag);
        }
    }

    /** Build a colon-separated cache key from non-empty parts. */
    public static function key(string ...$parts): string
    {
        return implode(':', array_filter($parts, fn ($part) => $part !== null && $part !== ''));
    }

    /** Use a tagged repository when supported, otherwise the default store. */
    private static function tagged(?string $tag): Repository
    {
        if ($tag && self::supportsTags()) {
            return self::store()->tags($tag);
        }

        return self::store();
    }
}
