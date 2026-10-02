<?php

namespace App\Services\Search;

use App\Models\AuthorAlias;

/** Builds RediSearch hash fields for an author alias document. */
class AuthorSearchDocumentBuilder
{
    /** @return array<string, string|int> */
    public function build(AuthorAlias $alias): array
    {
        $alias->loadMissing('user');

        return [
            'alias_id' => $alias->id,
            'username' => (string) $alias->username,
            'name' => (string) $alias->name,
            'bio' => (string) ($alias->bio ?? ''),
            'is_banned' => ($alias->user?->is_banned ?? false) ? 1 : 0,
        ];
    }

    public function key(AuthorAlias $alias): string
    {
        return config('search.redis.prefix', 'search:').'author:'.$alias->id;
    }

    public function isSearchable(AuthorAlias $alias): bool
    {
        if ($alias->trashed() || ($alias->user?->is_banned ?? false)) {
            return false;
        }

        return AuthorAlias::query()
            ->whereKey($alias->id)
            ->active()
            ->listedInDirectory()
            ->exists();
    }
}
