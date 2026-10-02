<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;

/** Resolve users for login, magic link, and verification flows. */
final class AuthUserLookup
{
    public static function findByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        $query = User::withoutGlobalScope('tenant')->where('email', $email);

        if (Tenancy::enabled()) {
            $tenantId = TenantContext::id();

            $query->where(function ($builder) use ($tenantId): void {
                $builder->where('tenant_id', $tenantId)
                    ->orWhere('role', UserRole::SuperAdmin);
            });
        } else {
            $query->where(function ($builder): void {
                $builder->where('tenant_id', Tenancy::defaultTenantId())
                    ->orWhere('role', UserRole::SuperAdmin);
            });
        }

        return $query->first();
    }
}
