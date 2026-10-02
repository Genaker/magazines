<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\Tenancy\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAuthorSubdomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('multi_tenancy', true);
        Features::set('author_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me']);
    }

    public function test_platform_super_admin_profile_works_on_subdomain(): void
    {
        TenantDomain::query()->create([
            'tenant_id' => \App\Models\Tenant::default()->id,
            'host' => 'lvh.me',
            'is_primary' => true,
        ]);
        Tenancy::flushRegisteredTenantHostsCache();

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'username' => 'superadmin',
            'name' => 'Super Admin',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->withServerVariables(['HTTP_HOST' => 'superadmin.lvh.me'])
            ->get('http://superadmin.lvh.me/')
            ->assertOk()
            ->assertSee('Super Admin');

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->get(route('authors.show', $admin->primaryAlias()))
            ->assertOk()
            ->assertSee('Super Admin');
    }
}
