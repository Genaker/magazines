<?php

namespace Tests\Feature\Tenancy;

use App\Models\Magazine;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\SubdomainSession;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('SESSION_DOMAIN');
        unset($_ENV['SESSION_DOMAIN'], $_SERVER['SESSION_DOMAIN']);
        config(['session.domain' => null]);

        Features::seedDefaults();
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
        Features::set('multi_tenancy', true);
        Features::set('author_subdomains', true);
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        Tenancy::flushRegisteredTenantHostsCache();
    }

    public function test_cookie_domain_is_per_tenant_not_global_lvh_me(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => Tenant::query()->where('slug', 'default')->value('id'),
            'host' => 'default.lvh.me',
            'is_primary' => true,
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
        Tenancy::flushRegisteredTenantHostsCache();

        putenv('SESSION_DOMAIN=.lvh.me');
        $_ENV['SESSION_DOMAIN'] = '.lvh.me';

        $this->assertSame('.default.lvh.me', SubdomainSession::cookieDomain('default.lvh.me'));
        $this->assertSame('.tenant1.lvh.me', SubdomainSession::cookieDomain('tenant1.lvh.me'));
        $this->assertSame('.tenant1.lvh.me', SubdomainSession::cookieDomain('author.tenant1.lvh.me'));
        $this->assertSame('.tenant1.lvh.me', SubdomainSession::cookieDomain('weekly.tenant1.lvh.me'));
        $this->assertSame('.default.lvh.me', SubdomainSession::cookieDomain('john.default.lvh.me'));
        $this->assertNull(SubdomainSession::cookieDomain('lvh.me'));
    }

    public function test_login_on_one_tenant_does_not_authenticate_on_another(): void
    {
        $defaultTenant = Tenant::query()->where('slug', 'default')->firstOrFail();
        $tenant1 = Tenant::query()->create([
            'name' => 'Tenant One',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $defaultTenant->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $tenant1->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);
        Tenancy::flushRegisteredTenantHostsCache();

        $defaultUser = User::factory()->create([
            'tenant_id' => $defaultTenant->id,
            'email' => 'user@default.test',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'tenant_id' => $tenant1->id,
            'email' => 'user@tenant1.test',
            'email_verified_at' => now(),
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'default.lvh.me'])
            ->post('http://default.lvh.me/login', [
                'email' => $defaultUser->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertAuthenticated();

        $this->withServerVariables(['HTTP_HOST' => 'tenant1.lvh.me'])
            ->get('http://tenant1.lvh.me/')
            ->assertOk()
            ->assertSee(__('app.login'))
            ->assertDontSee(__('app.profile'));
    }

    public function test_author_subdomain_shares_session_within_same_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant One',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);
        Tenancy::flushRegisteredTenantHostsCache();

        TenantContext::set($tenant);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'username' => 'john',
            'email_verified_at' => now(),
        ]);

        TenantContext::reset();

        config(['app.url' => 'http://tenant1.lvh.me:8888']);
        SubdomainSession::configure();

        $this->withServerVariables(['HTTP_HOST' => 'tenant1.lvh.me'])
            ->post('http://tenant1.lvh.me/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertAuthenticated();

        $this->withServerVariables(['HTTP_HOST' => 'john.tenant1.lvh.me'])
            ->get('http://john.tenant1.lvh.me/')
            ->assertOk()
            ->assertSee(__('app.profile'))
            ->assertDontSee(__('app.login'));
    }

    public function test_magazine_subdomain_shares_session_within_same_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant One',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);
        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);
        Tenancy::flushRegisteredTenantHostsCache();

        TenantContext::set($tenant);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        Magazine::query()->create([
            'tenant_id' => $tenant->id,
            'owner_id' => $user->id,
            'name' => 'Weekly',
            'slug' => 'weekly',
        ]);

        TenantContext::reset();

        config(['app.url' => 'http://tenant1.lvh.me:8888']);
        SubdomainSession::configure();

        $this->withServerVariables(['HTTP_HOST' => 'tenant1.lvh.me'])
            ->post('http://tenant1.lvh.me/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertAuthenticated();

        $this->withServerVariables(['HTTP_HOST' => 'weekly.tenant1.lvh.me'])
            ->get('http://weekly.tenant1.lvh.me/')
            ->assertOk()
            ->assertSee(__('app.profile'))
            ->assertDontSee(__('app.login'));
    }
}
