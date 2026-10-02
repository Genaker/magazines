<?php

use App\Enums\UserRole;
use App\Models\AuthorAlias;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AuthorAlias::withoutGlobalScopes()
            ->where('is_primary', true)
            ->where('is_system', false)
            ->whereHas('user', fn ($query) => $query->withoutGlobalScope('tenant')->where('role', UserRole::SuperAdmin->value))
            ->whereDoesntHave('posts', fn ($query) => $query->withoutGlobalScope('tenant')->published())
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        // Cannot reliably distinguish bootstrap aliases from manually flagged ones.
    }
};
