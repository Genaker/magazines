<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LoginLink;
use App\Models\User;
use App\Support\AdminPath;
use App\Support\AdminSession;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('multi_tenancy', false);
    }

    public function test_guest_admin_redirects_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_cannot_use_admin_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'email_verified_at' => now(),
        ]);

        AdminSession::configure();

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_login_grants_dashboard_access(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email' => 'admin@magazines.test',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        AdminSession::configure();
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_session_uses_separate_cookie_name(): void
    {
        AdminSession::configure();

        $this->assertSame(AdminPath::cookiePath(), config('session.path'));
        $this->assertStringEndsWith('-admin-session', config('session.cookie'));
    }

    public function test_admin_magic_link_login_grants_dashboard_access(): void
    {
        config(['app.debug' => true]);

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email' => 'admin@magazines.test',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->from(route('admin.login'))
            ->post(route('admin.login.magic.send'), [
                'email' => $admin->email,
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status', 'magic-link-sent')
            ->assertSessionHas('dev_login_code');

        $code = session('dev_login_code');

        $this->post(route('admin.login.magic.verify.code'), [
            'email' => $admin->email,
            'code' => $code,
        ])->assertRedirect(route('admin.dashboard'));

        AdminSession::configure();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_cannot_complete_admin_magic_link_login(): void
    {
        config(['app.debug' => true]);

        $user = User::factory()->create([
            'role' => UserRole::User,
            'email_verified_at' => now(),
        ]);

        $this->post(route('admin.login.magic.send'), [
            'email' => $user->email,
        ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status', 'magic-link-sent')
            ->assertSessionMissing('dev_login_code');

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', 'token'),
            'code' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->from(route('admin.login'))
            ->post(route('admin.login.magic.verify.code'), [
                'email' => $user->email,
                'code' => '123456',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_change_email_then_send_shows_code_step(): void
    {
        config(['app.debug' => true]);

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['tenant_id' => null])->saveQuietly();

        $this->withSession(['magic_login_email' => $admin->email])
            ->get(route('admin.login', ['change_email' => 1]))
            ->assertRedirect(route('admin.login'))
            ->assertSessionMissing('magic_login_email');

        $this->from(route('admin.login'))
            ->post(route('admin.login.magic.send'), [
                'email' => $admin->email,
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('magic_login_email', $admin->email)
            ->assertSessionHas('dev_login_code');

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee(__('app.magic_code_intro'), false)
            ->assertSee('name="code"', false)
            ->assertDontSee(__('app.admin_magic_login_intro'), false);
    }
}
