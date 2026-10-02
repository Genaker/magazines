<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\Features;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('multi_tenancy', true);
    }

    public function test_super_admin_sees_tenants_menu_before_platform_domain_is_configured(): void
    {
        config(['tenancy.platform_domain' => '']);

        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->get('http://lvh.me/admin')
            ->assertOk()
            ->assertSee('Tenants');
    }

    public function test_super_admin_can_manage_tenants_on_platform_host(): void
    {
        config(['tenancy.platform_domain' => 'platform.test']);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::platform()->id,
            'host' => 'platform.test',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->get('http://platform.test/admin/tenants')
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee('Default');
    }

    public function test_tenant_admin_cannot_access_platform_admin(): void
    {
        config(['tenancy.platform_domain' => 'platform.test']);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::platform()->id,
            'host' => 'platform.test',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'tenant_id' => 1,
        ]);

        $this->actingAsAdmin($admin)
            ->get('http://platform.test/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_super_admin_can_create_tenant(): void
    {
        config(['tenancy.platform_domain' => 'platform.test']);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::platform()->id,
            'host' => 'platform.test',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->post('http://platform.test/admin/tenants', [
                'name' => 'Acme Blog',
                'slug' => 'acme',
                'host' => 'acme.test',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenants', ['slug' => 'acme']);
        $this->assertDatabaseHas('tenant_domains', ['host' => 'acme.test']);
    }

    public function test_super_admin_can_set_default_tenant_domain_to_apex_lvh_me(): void
    {
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->put(route('admin.tenants.update', Tenant::default()), [
                'name' => 'Default',
                'slug' => 'default',
                'host' => 'lvh.me',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenant_domains', [
            'tenant_id' => Tenant::default()->id,
            'host' => 'lvh.me',
        ]);

        Tenancy::flushRegisteredTenantHostsCache();

        $this->assertSame(
            Tenant::default()->id,
            app(TenantResolver::class)->resolve('lvh.me')->id,
        );

        $this->actingAsAdmin($admin)
            ->get('http://lvh.me/admin/tenants')
            ->assertOk();
    }

    public function test_tenant_domain_rejects_invalid_host(): void
    {
        config(['tenancy.platform_domain' => 'platform.test']);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::platform()->id,
            'host' => 'platform.test',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->post('http://platform.test/admin/tenants', [
                'name' => 'Bad',
                'slug' => 'bad',
                'host' => 'http://lvh.me:8888',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('host');
    }

    public function test_tenant_scope_selector_updates_session(): void
    {
        config(['tenancy.platform_domain' => 'platform.test']);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::platform()->id,
            'host' => 'platform.test',
            'is_primary' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Scoped Tenant',
            'slug' => 'scoped',
            'status' => 'active',
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->post('http://platform.test/admin/tenant-scope', [
                'tenant_id' => $tenant->id,
            ])
            ->assertRedirect();

        $this->assertSame($tenant->id, session('admin_tenant_id'));
    }
}
