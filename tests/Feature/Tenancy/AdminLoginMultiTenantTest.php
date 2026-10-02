<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\LoginLink;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\AdminSession;
use App\Support\Features;
use App\Support\Tenancy\Tenancy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('multi_tenancy', true);
    }

    public function test_admin_login_grants_platform_admin_access(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => \App\Models\Tenant::default()->id,
            'host' => 'lvh.me',
            'is_primary' => true,
        ]);
        Tenancy::flushRegisteredTenantHostsCache();

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

    public function test_admin_magic_link_login_grants_platform_admin_access(): void
    {
        config(['app.debug' => true]);

        TenantDomain::query()->create([
            'tenant_id' => \App\Models\Tenant::default()->id,
            'host' => 'lvh.me',
            'is_primary' => true,
        ]);
        Tenancy::flushRegisteredTenantHostsCache();

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email' => 'admin@magazines.test',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->from('http://lvh.me/admin/login')
            ->post('http://lvh.me/admin/login/magic-link', [
                'email' => $admin->email,
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status', 'magic-link-sent')
            ->assertSessionHas('dev_login_code');

        $code = session('dev_login_code');

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->post('http://lvh.me/admin/login/magic-link/code', [
                'email' => $admin->email,
                'code' => $code,
            ])
            ->assertRedirect(route('admin.dashboard'));

        AdminSession::configure();
        $this->assertAuthenticatedAs($admin);

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->get('http://lvh.me/admin/tenants')
            ->assertOk()
            ->assertSee('Tenants');
    }

    public function test_default_tenant_on_lvh_me_serves_homepage(): void
    {
        $this->seed(DatabaseSeeder::class);
        Tenancy::flushRegisteredTenantHostsCache();

        $this->get('http://lvh.me/')
            ->assertOk()
            ->assertSee('Getting Started with Laravel');
    }
}
