<?php

namespace Tests\Feature\Integration;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\AdminPath;
use App\Support\AdminSession;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Custom ADMIN_PATH: routes, login, dashboard, and default-path security notice.
 */
class AdminPathIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('multi_tenancy', false);
    }

    public function test_default_admin_path_shows_security_notice_on_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('app.admin_default_path_insecure'), false);
    }
}
