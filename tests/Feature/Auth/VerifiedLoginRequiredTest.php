<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedLoginRequiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config(['app.debug' => true]);
    }

    public function test_registration_sends_code_and_does_not_log_user_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertRedirectsToRegistrationCodeStep($response, 'test@example.com');
        $this->assertGuest();
    }

    public function test_unverified_user_cannot_password_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_unverified_user_can_sign_in_with_magic_code(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $this->from(route('login'))
            ->post(route('login.magic.send'), ['email' => 'unverified@example.com'])
            ->assertSessionHas('dev_login_code');

        $user = User::query()->where('email', 'unverified@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());

        $this->completeRegistrationLogin('unverified@example.com');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_register_code_verifies_email_and_logs_in(): void
    {
        $this->post('/register', [
            'name' => 'Verify Flow User',
            'username' => 'verifyflow',
            'email' => 'verifyflow@example.com',
            'magic_link_only' => '1',
        ])->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('magic_login_email', 'verifyflow@example.com')
            ->assertSessionHas('dev_login_code');

        $this->assertGuest();

        $user = User::query()->where('email', 'verifyflow@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());

        $this->completeRegistrationLogin('verifyflow@example.com');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
