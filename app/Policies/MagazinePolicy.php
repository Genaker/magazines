<?php

namespace App\Policies;

use App\Enums\MagazineMemberRole;
use App\Models\Magazine;
use App\Models\User;

class MagazinePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Magazine $magazine): bool
    {
        return $this->role($user, $magazine)?->canEditMagazine() ?? false;
    }

    public function reviewSubmissions(User $user, Magazine $magazine): bool
    {
        return $this->role($user, $magazine)?->canReviewSubmissions() ?? false;
    }

    public function reviewJoinRequests(User $user, Magazine $magazine): bool
    {
        return $this->role($user, $magazine)?->canReviewSubmissions() ?? false;
    }

    public function submit(User $user, Magazine $magazine): bool
    {
        return $this->role($user, $magazine) !== null;
    }

    private function role(User $user, Magazine $magazine): ?MagazineMemberRole
    {
        if ($magazine->owner_id === $user->id) {
            return MagazineMemberRole::Owner;
        }

        $role = $magazine->memberRole($user);

        return $role ? MagazineMemberRole::from($role) : null;
    }
}
