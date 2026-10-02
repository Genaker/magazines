<?php

namespace Tests\Feature\Tenancy;

use App\Models\Magazine;
use App\Models\Post;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TenantDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_tenant_with_user_and_posts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->where('slug', TenantDemoSeeder::TENANT_SLUG)->firstOrFail();

        $this->assertDatabaseHas('tenant_domains', [
            'tenant_id' => $tenant->id,
            'host' => TenantDemoSeeder::TENANT_HOST,
        ]);

        $this->assertTrue(
            User::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('email', TenantDemoSeeder::AUTHOR_EMAIL)
                ->exists(),
        );

        $this->assertSame(
            4,
            Post::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
        );

        $this->assertTrue(
            Magazine::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('slug', TenantDemoSeeder::MAGAZINE_SLUG)
                ->exists(),
        );
    }
}
