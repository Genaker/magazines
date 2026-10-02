<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Sync moderator pivot rows from a list of user emails. */
class ModeratorAssigner
{
    /**
     * @param  Model&object{moderators(): BelongsToMany<User, covariant Model>}  $model
     * @param  list<string>  $emails
     */
    public static function syncFromEmails(Model $model, array $emails): void
    {
        $emails = collect($emails)
            ->map(fn (string $email) => strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            $model->moderators()->detach();

            return;
        }

        $userIds = User::query()
            ->whereIn('email', $emails)
            ->pluck('id');

        $model->moderators()->sync($userIds);
    }

    /** @return list<string> */
    public static function emailsFor(Model $model): array
    {
        return $model->moderators()
            ->orderBy('email')
            ->pluck('email')
            ->all();
    }
}
