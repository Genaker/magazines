<?php

namespace Tests\Feature\Tenancy;

use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDomainSubdomainConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        SiteSetting::setPlatformValue('feature_multi_tenancy', '1');
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);
    }

    public function test_tenant_domain_works_when_author_subdomains_disabled(): void
    {
        Features::set('author_subdomains', false);
        Features::set('magazine_subdomains', false);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);

        $this->get('http://tenant1.lvh.me/')
            ->assertOk();
    }

    public function test_tenant_root_domain_works_when_subdomains_off_and_host_parses_as_label(): void
    {
        Features::set('author_subdomains', false);
        Features::set('magazine_subdomains', false);
        AuthorSubdomain::setBaseHost('lvh.me');

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);

        $this->get('http://tenant1.lvh.me/')
            ->assertOk();
    }

    public function test_tenant_domain_works_when_author_subdomains_enabled(): void
    {
        Features::set('author_subdomains', true);
        Features::set('magazine_subdomains', false);

        TenantDomain::query()->create([
            'tenant_id' => Tenant::default()->id,
            'host' => 'default.lvh.me',
            'is_primary' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'tenant1.lvh.me',
            'is_primary' => true,
        ]);

        $this->get('http://tenant1.lvh.me/')
            ->assertOk();
    }
}
