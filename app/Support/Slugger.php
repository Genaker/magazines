<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Generates unique URL slugs for a model column. */
class Slugger
{
    /** Generate a unique slug, appending -1, -2, … when the base is taken. */
    public static function unique(string $value, Model $model, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = SubdomainLabel::normalize(Str::slug($value));
        $slug = $base;
        $counter = 1;

        while (self::exists($model, $column, $slug, $ignoreId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /** Check whether another row already owns this slug. */
    private static function exists(Model $model, string $column, string $slug, ?int $ignoreId): bool
    {
        $query = $model->newQuery()->where($column, $slug);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
