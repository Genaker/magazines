<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Serializes Eloquent models for cache storage and restores them on read.
 *
 * Volatile attributes (e.g. view counts) are stripped on encode so callers can
 * hydrate fresh values separately via FreshPostCounts.
 */
class CacheSerializer
{
    /** Recursively encode models, collections, and paginators for cache storage. */
    public static function encode(mixed $value): mixed
    {
        if ($value instanceof Model) {
            $relations = []; // encoded nested relations keyed by relation name

            foreach ($value->getRelations() as $name => $relation) {
                $relations[$name] = self::encode($relation);
            }

            return [
                '__cache_type' => 'model',
                '__class' => $value::class,
                '__attributes' => self::stripVolatileAttributes($value),
                '__relations' => $relations,
            ];
        }

        if ($value instanceof EloquentCollection) {
            return [
                '__cache_type' => 'collection',
                '__class' => $value->getQueueableClass() ?? Model::class,
                '__items' => $value->map(fn (Model $item) => self::encode($item))->values()->all(),
            ];
        }

        if ($value instanceof LengthAwarePaginator) {
            return [
                '__cache_type' => 'paginator',
                '__items' => collect($value->items())->map(fn ($item) => self::encode($item))->all(),
                '__total' => $value->total(),
                '__per_page' => $value->perPage(),
                '__current_page' => $value->currentPage(),
                '__path' => $value->path(),
                '__page_name' => $value->getPageName(),
            ];
        }

        if (is_array($value)) {
            $encoded = [];

            foreach ($value as $key => $item) {
                $encoded[$key] = self::encode($item);
            }

            return $encoded;
        }

        return $value;
    }

    /** Restore a previously encoded cache payload back into PHP values. */
    public static function decode(mixed $value): mixed
    {
        if (is_array($value) && ! isset($value['__cache_type'])) {
            $decoded = [];

            foreach ($value as $key => $item) {
                $decoded[$key] = self::decode($item);
            }

            return $decoded;
        }

        if (! is_array($value) || ! isset($value['__cache_type'])) {
            return $value;
        }

        return match ($value['__cache_type']) {
            'model' => self::decodeModel($value),
            'collection' => self::decodeCollection($value),
            'paginator' => self::decodePaginator($value),
            default => $value,
        };
    }

    /**
     * Hydrate a single model and its nested relations from cache payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function decodeModel(array $payload): Model
    {
        /** @var class-string<Model> $class */
        $class = $payload['__class'];
        $model = $class::hydrate([$payload['__attributes']])->first();

        foreach ($payload['__relations'] ?? [] as $name => $relation) {
            $model->setRelation($name, self::decode($relation));
        }

        return $model;
    }

    /**
     * Rebuild an Eloquent collection from encoded model payloads.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function decodeCollection(array $payload): EloquentCollection
    {
        $items = collect($payload['__items'])
            ->map(fn (array $item) => self::decodeModel($item))
            ->all();

        return new EloquentCollection($items);
    }

    /**
     * Rebuild a length-aware paginator from encoded items and metadata.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function decodePaginator(array $payload): LengthAwarePaginator
    {
        $items = collect($payload['__items'])->map(fn ($item) => self::decode($item))->all();

        return new LengthAwarePaginator(
            $items,
            $payload['__total'],
            $payload['__per_page'],
            $payload['__current_page'],
            [
                'path' => $payload['__path'],
                'pageName' => $payload['__page_name'],
            ],
        );
    }

    /**
     * Remove attributes that should not be cached (e.g. views_count).
     *
     * @return array<string, mixed>
     */
    private static function stripVolatileAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();
        $volatile = config('entity-cache.volatile_attributes.'.$model::class, []); // e.g. views_count on Post

        foreach ($volatile as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }
}
