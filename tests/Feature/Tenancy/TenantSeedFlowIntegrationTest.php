<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\MagazineSubdomain;
use App\Support\Tenancy\Tenancy;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TenantDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end tenant flow using DatabaseSeeder + TenantDemoSeeder:
 * domains, author/magazine subdomains, publishing, admin scopes, isolation.
 */
class TenantSeedFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        config(['app.url' => 'http://lvh.me:8888']);

        $this->tenant = Tenant::query()->where('slug', TenantDemoSeeder::TENANT_SLUG)->firstOrFail();
        Tenancy::flushRegisteredTenantHostsCache();
    }

    public function test_tenant1_domain_serves_seed_posts_not_default_content(): void
    {
        $this->get('http://'.TenantDemoSeeder::TENANT_HOST.'/')
            ->assertOk()
            ->assertSee(TenantDemoSeeder::WELCOME_POST_TITLE)
            ->assertDontSee('Getting Started with Laravel');
    }

    public function test_default_domain_serves_default_seed_posts(): void
    {
        $this->get('http://'.TenantDemoSeeder::DEFAULT_HOST.'/')
            ->assertOk()
            ->assertSee('Getting Started with Laravel')
            ->assertDontSee(TenantDemoSeeder::WELCOME_POST_TITLE);
    }

    public function test_author_subdomain_on_tenant_domain(): void
    {
        $author = $this->tenantAuthor();

        $this->get('http://'.TenantDemoSeeder::AUTHOR_USERNAME.'.'.TenantDemoSeeder::TENANT_HOST.'/')
            ->assertOk()
            ->assertSee($author->name)
            ->assertSee(TenantDemoSeeder::WELCOME_POST_TITLE);
    }

    public function test_magazine_subdomain_on_tenant_domain(): void
    {
        $magazine = Magazine::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('slug', TenantDemoSeeder::MAGAZINE_SLUG)
            ->firstOrFail();

        $host = TenantDemoSeeder::MAGAZINE_SLUG.'.'.TenantDemoSeeder::TENANT_HOST;

        $this->get('http://'.$host.'/')
            ->assertOk()
            ->assertSee(TenantDemoSeeder::MAGAZINE_NAME)
            ->assertSee(TenantDemoSeeder::WELCOME_POST_TITLE);

        $post = Post::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('title', TenantDemoSeeder::WELCOME_POST_TITLE)
            ->firstOrFail();

        $this->assertSame(
            'http://'.$host.':8888/'.$post->slug,
            MagazineSubdomain::postUrl($post->load('magazine', 'authorAlias')),
        );
    }

    public function test_tenant_author_can_publish_post_on_tenant_domain(): void
    {
        $author = $this->tenantAuthor();
        $category = Category::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('slug', 'tenant1-community')
            ->firstOrFail();

        $this->actingAs($author)
            ->post('http://'.TenantDemoSeeder::TENANT_HOST.'/write', [
                'title' => 'Integration Tenant Published Story',
                'subtitle' => 'Created on tenant1.lvh.me',
                'body' => '<p>TenantSeedFlowIntegrationTest unique body.</p>',
                'category_id' => $category->id,
                'status' => 'published',
                'tags' => 'tenant, integration',
            ])
            ->assertRedirect();

        $post = Post::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('title', 'Integration Tenant Published Story')
            ->firstOrFail();

        $this->assertSame($this->tenant->id, $post->tenant_id);

        $this->get('http://'.TenantDemoSeeder::TENANT_HOST.'/')
            ->assertOk()
            ->assertSee('Integration Tenant Published Story');

        $alias = $this->tenantAuthorAlias($author);

        $this->followingRedirects()
            ->get('http://'.TenantDemoSeeder::TENANT_HOST.'/@'.$alias->username.'/'.$post->slug)
            ->assertOk()
            ->assertSee('TenantSeedFlowIntegrationTest unique body', false);
    }

    public function test_tenant_admin_can_access_admin_on_tenant_host(): void
    {
        $admin = User::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('email', TenantDemoSeeder::ADMIN_EMAIL)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get('http://'.TenantDemoSeeder::TENANT_HOST.'/admin/magazines')
            ->assertOk()
            ->assertSee(TenantDemoSeeder::MAGAZINE_NAME);
    }

    public function test_super_admin_can_manage_tenants_on_apex_host(): void
    {
        $superAdmin = User::withoutGlobalScope('tenant')
            ->where('email', 'admin@magazines.test')
            ->firstOrFail();

        $this->assertSame(UserRole::SuperAdmin, $superAdmin->role);

        $this->actingAs($superAdmin)
            ->get('http://lvh.me:8888/admin/tenants')
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee('Tenant 1');
    }

    public function test_author_url_uses_tenant_domain_as_subdomain_base(): void
    {
        $author = $this->tenantAuthor();
        $alias = $this->tenantAuthorAlias($author);

        $this->withServerVariables(['HTTP_HOST' => TenantDemoSeeder::TENANT_HOST])
            ->get('http://'.TenantDemoSeeder::TENANT_HOST.'/')
            ->assertOk();

        $this->assertSame(
            'http://'.TenantDemoSeeder::AUTHOR_USERNAME.'.'.TenantDemoSeeder::TENANT_HOST.':8888/',
            AuthorSubdomain::authorHomeUrl($alias),
        );
    }

    public function test_profile_sidebar_links_use_tenant_domain_on_nested_author_subdomain(): void
    {
        $author = $this->tenantAuthor();

        $this->actingAs($author)
            ->get('http://'.TenantDemoSeeder::TENANT_HOST.'/profile/aliases')
            ->assertOk()
            ->assertSee('http://'.TenantDemoSeeder::TENANT_HOST.':8888/profile"', false)
            ->assertSee('http://'.TenantDemoSeeder::TENANT_HOST.':8888/profile/aliases"', false)
            ->assertSee('http://'.TenantDemoSeeder::TENANT_HOST.':8888/me/posts"', false)
            ->assertDontSee('http://lvh.me:8888/profile"', false);
    }

    private function tenantAuthor(): User
    {
        return User::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('email', TenantDemoSeeder::AUTHOR_EMAIL)
            ->firstOrFail();
    }

    private function tenantAuthorAlias(User $author): AuthorAlias
    {
        return AuthorAlias::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $author->id)
            ->where('is_primary', true)
            ->firstOrFail();
    }
}
