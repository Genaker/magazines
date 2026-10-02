<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthUserLookupMagicLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magic_link_login', true);
        config(['app.debug' => true]);
    }

    public function test_super_admin_with_null_tenant_id_receives_magic_login_email(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email' => 'super@example.com',
        ]);

        $this->assertNull($admin->tenant_id);

        $this->post(route('login.magic.send'), ['email' => 'super@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'magic-link-sent');

        $code = session('dev_login_code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $code);

        $this->post(route('login.magic.verify.code'), [
            'email' => 'super@example.com',
            'code' => $code,
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($admin);

        $this->assertDatabaseCount('login_links', 1);
        $this->assertDatabaseHas('login_links', ['email' => 'super@example.com']);
    }
}
