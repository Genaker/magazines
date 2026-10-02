<?php

use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::withoutGlobalScope('tenant')
            ->where('role', UserRole::SuperAdmin->value)
            ->update(['is_system' => false]);

        User::withoutGlobalScope('tenant')->each(function (User $user): void {
            if ($user->authorAliases()->exists()) {
                return;
            }

            AuthorAlias::createFromUser($user, isPrimary: true);
        });
    }

    public function down(): void
    {
        // Not reversible — prior is_system flags were incorrect for human super admins.
    }
};
