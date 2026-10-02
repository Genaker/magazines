<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** MySQL/MariaDB/PostgreSQL full-text match helpers for the database search driver. */
class DatabaseFullTextSearch
{
    /** @return list<string> */
    private static function supportedDrivers(): array
    {
        return ['mysql', 'mariadb', 'pgsql'];
    }

    public static function supports(): bool
    {
        return in_array(
            Schema::getConnection()->getDriverName(),
            self::supportedDrivers(),
            true,
        );
    }

    /** Use SQL LIKE when MySQL/MariaDB FULLTEXT cannot see uncommitted rows (PHPUnit transactions). */
    public static function useLikeFallback(): bool
    {
        static::ensureSupported();

        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        $pdo = Schema::getConnection()->getPdo();

        return $pdo !== null && $pdo->inTransaction();
    }

    /**
     * Match the term against any of the given columns (OR).
     *
     * @param  EloquentBuilder|QueryBuilder  $query
     * @param  list<string>  $columns
     */
    public static function matchAny(EloquentBuilder|QueryBuilder $query, array $columns, string $term): void
    {
        $term = trim($term);

        if ($term === '' || $columns === []) {
            return;
        }

        static::ensureSupported();

        if (static::useLikeFallback()) {
            static::applyLike($query, $columns, $term, 'where');

            return;
        }

        static::applyFullText($query, $columns, $term, 'where');
    }

    /**
     * OR-match the term against any of the given columns inside a wider where group.
     *
     * @param  EloquentBuilder|QueryBuilder  $query
     * @param  list<string>  $columns
     */
    public static function orMatchAny(EloquentBuilder|QueryBuilder $query, array $columns, string $term): void
    {
        $term = trim($term);

        if ($term === '' || $columns === []) {
            return;
        }

        static::ensureSupported();

        if (static::useLikeFallback()) {
            static::applyLike($query, $columns, $term, 'or');

            return;
        }

        static::applyFullText($query, $columns, $term, 'or');
    }

    /**
     * @param  EloquentBuilder|QueryBuilder  $query
     * @param  list<string>  $columns
     */
    private static function applyLike(EloquentBuilder|QueryBuilder $query, array $columns, string $term, string $boolean): void
    {
        $callback = function (EloquentBuilder|QueryBuilder $builder) use ($columns, $term): void {
            foreach ($columns as $index => $column) {
                $builder->{$index === 0 ? 'where' : 'orWhere'}($column, 'like', "%{$term}%");
            }
        };

        if ($boolean === 'or') {
            $query->orWhere($callback);

            return;
        }

        $query->where($callback);
    }

    /**
     * @param  EloquentBuilder|QueryBuilder  $query
     * @param  list<string>  $columns
     */
    private static function applyFullText(EloquentBuilder|QueryBuilder $query, array $columns, string $term, string $boolean): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            static::applyPostgresFullText($query, $columns, $term, $boolean);

            return;
        }

        $method = $boolean === 'or' ? 'orWhereFullText' : 'whereFullText';
        $query->{$method}($columns, $term);
    }

    /**
     * PostgreSQL NULL text columns make `||` chains NULL — coalesce each tsvector.
     *
     * @param  EloquentBuilder|QueryBuilder  $query
     * @param  list<string>  $columns
     */
    private static function applyPostgresFullText(EloquentBuilder|QueryBuilder $query, array $columns, string $term, string $boolean): void
    {
        /** @var Grammar $grammar */
        $grammar = Schema::getConnection()->getQueryGrammar();
        $language = 'english';

        $vectors = collect($columns)
            ->map(fn (string $column): string => sprintf(
                "coalesce(to_tsvector('%s', %s), '')",
                $language,
                $grammar->wrap($column),
            ))
            ->implode(' || ');

        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';
        $query->{$method}("({$vectors}) @@ plainto_tsquery('{$language}', ?)", [$term]);
    }

    private static function ensureSupported(): void
    {
        if (! static::supports()) {
            throw new RuntimeException(
                'Database full-text search requires MySQL, MariaDB, or PostgreSQL (current driver: '
                .Schema::getConnection()->getDriverName().').',
            );
        }
    }
}
