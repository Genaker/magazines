<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Passwordless registration: PIN verifies email and signs in → optional password.
 */
class PasswordlessRegistrationFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config(['app.debug' => true]);
    }

    public function test_passwordless_register_code_login_set_password_then_password_login(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Passwordless Writer',
            'username' => 'passwordlesswriter',
            'email' => 'passwordless@integration.test',
            'magic_link_only' => '1',
        ]);

        $this->assertRedirectsToRegistrationCodeStep($response, 'passwordless@integration.test');

        $this->assertGuest();

        $user = User::query()->where('email', 'passwordless@integration.test')->firstOrFail();
        $this->assertFalse($user->hasPassword());

        $this->completeRegistrationLogin('passwordless@integration.test');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->from('/profile')
            ->put('/password', [
                'password' => 'chosen-password',
                'password_confirmation' => 'chosen-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue($user->refresh()->hasPassword());
        $this->assertTrue(Hash::check('chosen-password', $user->password));

        $this->post(route('logout'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'chosen-password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_passwordless_user_can_set_password_via_forgot_password_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@integration.test',
            'username' => 'resetme',
            'password' => null,
            'email_verified_at' => now(),
        ]);

        $this->assertFalse($user->hasPassword());

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'reset-password',
                'password_confirmation' => 'reset-password',
            ])
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('reset-password', $user->refresh()->password));
    }

    public function test_passwordless_user_can_update_password_after_setting_one_from_profile(): void
    {
        $user = User::factory()->create([
            'password' => null,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'first-password',
                'password_confirmation' => 'first-password',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user->refresh())
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'first-password',
                'password' => 'second-password',
                'password_confirmation' => 'second-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('second-password', $user->refresh()->password));
    }
}
