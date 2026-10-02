<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\NotificationSender;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** Broadcasts notifications to all admin and super-admin users. */
class AdminNotifier
{
    /**
     * All admin and super-admin accounts eligible for system notifications.
     *
     * @return Collection<int, User>
     */
    public static function admins(): Collection
    {
        $tenantId = TenantContext::scopeTenantId() ?? Tenancy::defaultTenantId();

        return User::withoutGlobalScope('tenant')
            ->where(function ($query) use ($tenantId): void {
                $query->where(function ($tenantAdmins) use ($tenantId): void {
                    $tenantAdmins->where('tenant_id', $tenantId)
                        ->whereIn('role', [UserRole::Admin->value, UserRole::SuperAdmin->value]);
                })->orWhere(function ($platformSuperAdmins): void {
                    $platformSuperAdmins->whereNull('tenant_id')
                        ->where('role', UserRole::SuperAdmin->value);
                });
            })
            ->get();
    }

    /** Send the same notification to every admin account. */
    public static function notify(Notification $notification): void
    {
        NotificationSender::sendToMany(self::admins(), $notification);
    }
}
