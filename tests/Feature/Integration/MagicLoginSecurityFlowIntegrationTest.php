<?php

namespace Tests\Feature\Integration;

use App\Models\LoginLink;
use App\Models\User;
use App\Support\Features;
use App\Support\MagicLoginLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Magic-link security: PIN verifies email on login, failed-attempt lockout, CLI unlock.
 */
class MagicLoginSecurityFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config([
            'app.debug' => true,
            'magic-login.max_failed_attempts' => 3,
        ]);
    }

    public function test_password_registration_requires_pin_before_password_login(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Secure Writer',
            'username' => 'securewriter',
            'email' => 'secure@integration.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertRedirectsToRegistrationCodeStep($response, 'secure@integration.test');
        $this->assertGuest();

        $user = User::query()->where('email', 'secure@integration.test')->firstOrFail();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->completeRegistrationLogin('secure@integration.test');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->post(route('logout'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_magic_codes_lock_account_until_cli_unlock(): void
    {
        $user = User::factory()->create([
            'email' => 'lockflow@integration.test',
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.magic.send'), ['email' => $user->email])
            ->assertSessionHas('dev_login_code');

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login.magic.verify.code'), [
                'email' => $user->email,
                'code' => '000000',
            ])->assertSessionHasErrors('code');
        }

        $this->assertTrue(MagicLoginLock::isLocked($user->fresh()));

        $this->from(route('login'))
            ->post(route('login.magic.send'), ['email' => $user->email])
            ->assertSessionHasErrors('email');

        $this->artisan('app:user:unlock-magic-login', ['email' => $user->email])
            ->assertSuccessful();

        $this->post(route('login.magic.send'), ['email' => $user->email])
            ->assertSessionHas('dev_login_code');

        $this->post(route('login.magic.verify.code'), [
            'email' => $user->email,
            'code' => session('dev_login_code'),
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_magic_link_token_counts_toward_lockout(): void
    {
        $user = User::factory()->create([
            'email' => 'tokenfail@integration.test',
            'email_verified_at' => now(),
        ]);

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', 'valid-token'),
            'code' => hash('sha256', '111111'),
            'expires_at' => now()->addMinutes(15),
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->get(route('login.magic.verify', [
                'email' => $user->email,
                'token' => 'wrong-token-'.Str::random(8),
            ]))->assertRedirect(route('login'));
        }

        $this->assertTrue(MagicLoginLock::isLocked($user->fresh()));
    }
}
