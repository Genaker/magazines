<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\Features;
use App\Support\SubdomainSession;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAlternateApexDomainTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $lvh2Tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
        Features::set('multi_tenancy', true);
        Features::set('author_subdomains', true);
        config(['app.url' => 'http://lvh.me:8888']);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'lvh.me',
            'is_primary' => true,
        ]);

        $this->lvh2Tenant = Tenant::query()->create([
            'name' => 'Tenant Two',
            'slug' => 'tenant2',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::query()->create([
                'name' => 'Tenant One',
                'slug' => 'tenant1',
                'status' => 'active',
            ])->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $this->lvh2Tenant->id,
            'host' => 'lvh2.me',
            'is_primary' => true,
        ]);

        Tenancy::flushRegisteredTenantHostsCache();
    }

    public function test_lvh2_me_resolves_to_its_tenant(): void
    {
        $resolver = app(TenantResolver::class);

        $this->assertSame(Tenant::default()->id, $resolver->resolve('lvh.me')->id);
        $this->assertSame($this->lvh2Tenant->id, $resolver->resolve('lvh2.me')->id);
    }

    public function test_lvh2_me_is_not_treated_as_platform_app_top_domain(): void
    {
        $this->assertTrue(Tenancy::isAppTopDomain('lvh.me'));
        $this->assertFalse(Tenancy::isAppTopDomain('lvh2.me'));
    }

    public function test_separate_apex_domain_uses_dot_cookie_for_session_sharing(): void
    {
        $this->assertTrue(Tenancy::registeredSubdomainTenantExists('lvh.me'));
        $this->assertFalse(Tenancy::registeredSubdomainTenantExists('lvh2.me'));

        $this->assertNull(SubdomainSession::cookieDomain('lvh.me'));
        $this->assertSame('.lvh2.me', SubdomainSession::cookieDomain('lvh2.me'));
        $this->assertSame('.lvh2.me', SubdomainSession::cookieDomain('author.lvh2.me'));
        $this->assertSame('.tenant1.lvh.me', SubdomainSession::cookieDomain('tenant1.lvh.me'));
    }

    public function test_author_subdomain_under_separate_apex_resolves_tenant(): void
    {
        TenantContext::set($this->lvh2Tenant);

        User::factory()->create([
            'tenant_id' => $this->lvh2Tenant->id,
            'username' => 'jane',
            'name' => 'Jane Lvh2',
        ]);

        TenantContext::reset();

        $this->get('http://jane.lvh2.me/')
            ->assertOk()
            ->assertSee('Jane Lvh2');
    }

    public function test_login_on_lvh2_me_does_not_authenticate_on_lvh_me(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->lvh2Tenant->id,
            'email' => 'user@lvh2.test',
            'email_verified_at' => now(),
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'lvh2.me'])
            ->post('http://lvh2.me/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertAuthenticated();

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->get('http://lvh.me/')
            ->assertOk()
            ->assertSee(__('app.login'))
            ->assertDontSee(__('app.profile'));
    }

    public function test_super_admin_can_create_tenant_with_separate_apex_domain(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAsAdmin($admin)
            ->post(route('admin.tenants.store'), [
                'name' => 'Tenant Three',
                'slug' => 'tenant3',
                'host' => 'lvh3.me',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenant_domains', ['host' => 'lvh3.me']);

        Tenancy::flushRegisteredTenantHostsCache();

        $this->assertSame(
            Tenant::query()->where('slug', 'tenant3')->value('id'),
            app(TenantResolver::class)->resolve('lvh3.me')->id,
        );
    }
}
