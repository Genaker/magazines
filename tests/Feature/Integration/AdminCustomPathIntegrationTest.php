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
 * App booted with ADMIN_PATH=desk — custom admin URL routes and login.
 */
class AdminCustomPathIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('ADMIN_PATH=desk');
        $_ENV['ADMIN_PATH'] = 'desk';
        $_SERVER['ADMIN_PATH'] = 'desk';

        parent::setUp();

        Features::seedDefaults();
        Features::set('multi_tenancy', false);
    }

    protected function tearDown(): void
    {
        putenv('ADMIN_PATH=admin');
        unset($_ENV['ADMIN_PATH'], $_SERVER['ADMIN_PATH']);

        parent::tearDown();
    }

    public function test_custom_admin_path_login_and_dashboard_work(): void
    {
        $this->assertSame('desk', AdminPath::prefix());
        $this->assertFalse(AdminPath::usesDefault());

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email' => 'admin@magazines.test',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->get('/admin/login/password')->assertNotFound();
        $this->get('/desk/login/password')->assertOk();

        $this->post('/desk/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        AdminSession::configure();
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(__('app.admin_default_path_insecure'), false);

        $this->get(route('admin.users.index'))->assertOk();
    }
}
