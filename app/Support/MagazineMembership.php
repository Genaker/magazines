<?php

namespace App\Support;

use App\Models\Magazine;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Magazine roster checks for contributors. */
final class MagazineMembership
{
    public static function assertCanSubmit(User $user, Magazine $magazine): void
    {
        if (! Gate::forUser($user)->allows('submit', $magazine)) {
            throw ValidationException::withMessages([
                'magazine_id' => __('message.magazine_not_member'),
            ]);
        }
    }
}
