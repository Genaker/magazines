<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\Magazine;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\MagazineSubdomain;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantNestedSubdomainTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
        Features::set('author_subdomains', true);
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $this->tenant = Tenant::query()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $this->tenant->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);

        Tenancy::flushRegisteredTenantHostsCache();
    }

    public function test_author_subdomain_works_under_tenant_domain(): void
    {
        TenantContext::set($this->tenant);

        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'john',
            'name' => 'John Tenant',
        ]);

        TenantContext::reset();

        $request = \Illuminate\Http\Request::create('http://john.tenant1.lvh.me/');
        $this->assertSame('john', AuthorSubdomain::usernameFromHost($request));
        $this->assertSame($this->tenant->id, AuthorAlias::query()->withoutGlobalScopes()->where('username', 'john')->value('tenant_id'));

        $this->get('http://john.tenant1.lvh.me/')
            ->assertOk()
            ->assertSee('John Tenant');
    }

    public function test_magazine_subdomain_works_under_tenant_domain(): void
    {
        TenantContext::set($this->tenant);

        $owner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Magazine::query()->create([
            'tenant_id' => $this->tenant->id,
            'owner_id' => $owner->id,
            'name' => 'Tenant Magazine',
            'slug' => 'tenant-mag',
            'description' => 'Scoped magazine',
        ]);

        TenantContext::reset();

        $this->get('http://tenant-mag.tenant1.lvh.me/')
            ->assertOk()
            ->assertSee('Tenant Magazine');
    }

    public function test_tenant_admin_can_open_magazine_admin_on_tenant_domain(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($admin)
            ->get('http://tenant1.lvh.me/admin/magazines')
            ->assertOk();
    }

    public function test_author_url_uses_tenant_domain_as_base(): void
    {
        TenantContext::set($this->tenant);

        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'john',
        ]);

        $alias = $user->authorAliases()->withoutGlobalScopes()->where('is_primary', true)->firstOrFail();

        TenantContext::reset();

        $this->assertSame(
            'http://john.tenant1.lvh.me:8888/',
            AuthorSubdomain::authorHomeUrl($alias),
        );
    }

    public function test_author_on_other_tenant_is_not_visible(): void
    {
        $otherTenant = Tenant::query()->create([
            'name' => 'Tenant 2',
            'slug' => 'tenant2',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $otherTenant->id,
            'host' => 'tenant2.lvh.me',
            'is_primary' => true,
        ]);

        TenantContext::set($otherTenant);

        User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'username' => 'john',
            'name' => 'Other John',
        ]);

        TenantContext::reset();

        $this->get('http://john.tenant1.lvh.me/')
            ->assertNotFound();
    }
}
