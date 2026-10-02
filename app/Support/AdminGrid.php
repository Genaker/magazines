<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Sort and search helpers for admin listing grids. */
class AdminGrid
{
    /**
     * Parse sort, direction, and search query from an admin grid request.
     *
     * @param  list<string>  $allowedSorts
     * @return array{sort: string, dir: string, search: string}
     */
    public static function params(Request $request, array $allowedSorts, string $defaultSort = 'created_at'): array
    {
        $sort = in_array($request->query('sort'), $allowedSorts, true)
            ? (string) $request->query('sort')
            : $defaultSort;

        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('q', ''));

        return compact('sort', 'dir', 'search');
    }

    /**
     * Apply a LIKE search across one or more columns.
     *
     * @param  list<string>  $columns
     */
    public static function applySearch(Builder $query, string $search, array $columns): Builder
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($search, $columns): void {
            foreach ($columns as $index => $column) {
                $inner->{$index === 0 ? 'where' : 'orWhere'}($column, 'like', "%{$search}%");
            }
        });
    }
}
