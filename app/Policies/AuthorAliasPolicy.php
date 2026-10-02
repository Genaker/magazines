<?php

namespace App\Policies;

use App\Models\AuthorAlias;
use App\Models\User;

class AuthorAliasPolicy
{
    public function use(User $user, AuthorAlias $alias): bool
    {
        return $user->id === $alias->user_id && ! $alias->trashed();
    }

    public function delete(User $user, AuthorAlias $alias): bool
    {
        return $user->id === $alias->user_id && ! $alias->trashed();
    }
}
