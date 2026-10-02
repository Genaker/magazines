<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\SiteUrl;
use App\Support\Tenancy\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApexUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
        config(['app.url' => 'http://lvh.me:8888']);
        AuthorSubdomain::setBaseHost('lvh.me');
    }

    public function test_super_admin_admin_link_uses_apex_not_tenant_subdomain(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'default2',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenantB->id,
            'host' => 'default2.lvh.me',
            'is_primary' => true,
        ]);

        Tenancy::flushRegisteredTenantHostsCache();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin);
        request()->headers->set('HOST', 'default2.lvh.me');

        $this->assertSame(
            'http://lvh.me:8888/admin',
            SiteUrl::navRoute('admin.dashboard'),
        );

        $this->actingAsAdmin($admin)
            ->get('http://default2.lvh.me/admin')
            ->assertRedirect('http://lvh.me:8888/admin');
    }
}
