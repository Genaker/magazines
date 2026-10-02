<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\Features;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenancyContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_multi_tenancy_disabled_scopes_to_default_tenant(): void
    {
        SiteSetting::setPlatformValue('feature_multi_tenancy', '0');

        $this->assertFalse(Tenancy::enabled());
        $this->assertSame(1, TenantContext::scopeTenantId());

        $user = User::factory()->create(['email' => 'scoped@example.com']);

        $this->assertSame(1, $user->tenant_id);
    }

    public function test_multi_tenancy_disabled_respects_explicit_tenant_context(): void
    {
        SiteSetting::setPlatformValue('feature_multi_tenancy', '0');

        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        TenantContext::set($tenantB);

        $this->assertSame($tenantB->id, TenantContext::scopeTenantId());
    }

    public function test_super_admin_has_null_tenant_id(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->assertNull($admin->tenant_id);
    }

    public function test_enabled_mode_isolates_posts_by_tenant(): void
    {
        TenantContext::reset();
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
        $this->assertTrue(Tenancy::enabled());

        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        $authorA = User::factory()->create(['tenant_id' => 1]);
        $authorB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $postA = new Post([
            'user_id' => $authorA->id,
            'author_alias_id' => $authorA->primaryAlias()->id,
            'title' => 'Post A',
            'slug' => 'post-a',
            'type' => \App\Enums\PostType::Article,
            'body' => 'Body',
            'status' => 'draft',
        ]);
        $postA->tenant_id = 1;
        $postA->saveQuietly();

        $postB = new Post([
            'user_id' => $authorB->id,
            'author_alias_id' => $authorB->primaryAlias()->id,
            'title' => 'Post B',
            'slug' => 'post-b',
            'type' => \App\Enums\PostType::Article,
            'body' => 'Body',
            'status' => 'draft',
        ]);
        $postB->tenant_id = $tenantB->id;
        $postB->saveQuietly();

        TenantContext::setAdminScope(1);
        $this->assertSame(1, TenantContext::scopeTenantId());
        $this->assertSame(1, Post::query()->count());

        TenantContext::setAdminScope($tenantB->id);
        $this->assertSame(1, Post::query()->count());
        $this->assertSame('Post B', Post::query()->value('title'));
    }

    public function test_tenant_resolver_returns_model_from_cached_tenant_id(): void
    {
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'cached-tenant.test',
            'is_primary' => true,
        ]);

        $resolver = app(TenantResolver::class);

        $first = $resolver->resolve('cached-tenant.test');
        $this->assertInstanceOf(Tenant::class, $first);

        Cache::put('tenant:host:cached-tenant.test', $first->id, 3600);

        $second = $resolver->resolve('cached-tenant.test');
        $this->assertInstanceOf(Tenant::class, $second);
        $this->assertSame($first->id, $second->id);
    }

    public function test_enabling_multi_tenancy_clears_tenant_host_cache(): void
    {
        SiteSetting::setPlatformValue('feature_multi_tenancy', '0');

        Cache::put('tenant:host:stale.test', 999, 3600);

        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
        Features::set('multi_tenancy', true);

        $this->assertNull(Cache::get('tenant:host:stale.test'));
        $this->assertTrue(Tenancy::enabled());
    }
}
