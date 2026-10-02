<?php

namespace App\Support;

use App\Enums\CategoryRequestStatus;
use App\Models\CategoryRequest;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserReport;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\Tenancy;

/** Compact stats and labels for the admin top bar. */
final class AdminHeader
{
    /** @return array{siteName: string, stats: array<string, int>, tenantLabel: ?string, quickLinks: list<array{label: string, route: string, active: bool}>} */
    public static function data(): array
    {
        $user = auth()->user();

        $stats = [
            'users' => User::query()->count(),
            'posts' => Post::query()->count(),
        ];

        if (Features::enabled('user_reports')) {
            $stats['reports'] = UserReport::query()->count();
        }

        if (Features::enabled('category_requests')) {
            $stats['pending_requests'] = CategoryRequest::query()
                ->where('status', CategoryRequestStatus::Pending)
                ->count();
        }

        return [
            'siteName' => SiteSetting::getValue('site_name', config('app.name')),
            'stats' => $stats,
            'tenantLabel' => self::tenantLabel(),
            'quickLinks' => self::quickLinks($user?->isSuperAdmin() ?? false),
        ];
    }

    private static function tenantLabel(): ?string
    {
        if (! Tenancy::enabled() || ! auth()->user()?->isSuperAdmin()) {
            return Tenancy::enabled() && TenantContext::current()
                ? TenantContext::current()->name
                : null;
        }

        if (! TenantContext::allowsPlatformTenantManagement()) {
            return TenantContext::current()?->name;
        }

        $scopeId = session('admin_tenant_id');

        if ($scopeId === null) {
            return 'All tenants';
        }

        return Tenant::query()->whereKey($scopeId)->value('name') ?? 'All tenants';
    }

    /** @return list<array{label: string, route: string, active: bool}> */
    private static function quickLinks(bool $isSuperAdmin): array
    {
        $links = [
            ['label' => 'Users', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*')],
            ['label' => 'Posts', 'route' => 'admin.posts.index', 'active' => request()->routeIs('admin.posts.*')],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'active' => request()->routeIs('admin.categories.*')],
        ];

        if ($isSuperAdmin) {
            $links[] = [
                'label' => 'Settings',
                'route' => 'admin.settings.edit',
                'active' => request()->routeIs('admin.settings.*'),
            ];
        }

        return $links;
    }
}
