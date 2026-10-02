<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthUserLookupMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('multi_tenancy', true);
        Features::set('magic_link_login', true);
        config(['app.debug' => true]);
    }

    public function test_super_admin_can_log_in_from_non_default_tenant_host(): void
    {
        $tenant = \App\Models\Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenantb',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'tenantb.test',
            'is_primary' => true,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email' => 'super@example.com',
        ]);

        $admin->forceFill(['tenant_id' => 1])->saveQuietly();

        $this->post('http://tenantb.test/login', [
            'email' => 'super@example.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }
}
