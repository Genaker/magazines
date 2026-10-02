<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
    }

    public function test_unmapped_host_uses_default_tenant_when_default_has_no_domain(): void
    {
        $resolver = app(TenantResolver::class);

        $tenant = $resolver->resolve('lvh.me');

        $this->assertTrue($tenant->is_default);
        $this->assertSame('default', $tenant->slug);
    }

    public function test_unmapped_host_returns_404_when_default_has_explicit_domain(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $this->get('http://default2.lvh.me/')
            ->assertNotFound();

        $this->get('http://lvh.me/')
            ->assertNotFound();

        $this->get('http://default.lvh.me/')
            ->assertOk();
    }

    public function test_mapped_host_uses_that_tenant_not_default(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'lvh.me',
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

        $resolver = app(TenantResolver::class);

        $this->assertSame(Tenant::default()->id, $resolver->resolve('lvh.me')->id);
        $this->assertSame($tenantB->id, $resolver->resolve('default2.lvh.me')->id);
    }

    public function test_suspended_tenant_on_mapped_host_returns_404(): void
    {
        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'default2',
            'status' => 'suspended',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenantB->id,
            'host' => 'default2.lvh.me',
            'is_primary' => true,
        ]);

        $this->get('http://default2.lvh.me/')
            ->assertNotFound();
    }

    public function test_unmapped_host_still_serves_default_when_other_tenant_is_mapped(): void
    {
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

        $this->get('http://lvh.me/')
            ->assertOk();

        $this->get('http://default2.lvh.me/')
            ->assertOk();
    }

    public function test_admin_works_on_app_top_domain_when_default_has_strict_domain(): void
    {
        config(['app.url' => 'http://lvh.me:8888']);
        AuthorSubdomain::setBaseHost('lvh.me');

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->get('http://lvh.me/admin/tenants')
            ->assertOk()
            ->assertSee('Tenants');

        $this->get('http://lvh.me/')
            ->assertNotFound();

        $this->get('http://default2.lvh.me/admin/tenants')
            ->assertNotFound();
    }

    public function test_login_works_on_app_top_domain_when_default_has_strict_domain(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $this->get('http://lvh.me/admin/login')
            ->assertOk();

        $this->get('http://lvh.me/')
            ->assertNotFound();
    }

    public function test_super_admin_can_login_on_apex_and_open_platform_admin(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email' => 'admin@magazines.test',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->post('http://lvh.me/admin/login', [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->get('http://lvh.me/admin/tenants')
            ->assertOk()
            ->assertSee('Tenants');
    }
}
