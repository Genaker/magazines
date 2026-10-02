<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config(['app.debug' => true]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertRedirectsToRegistrationCodeStep($response, 'test@example.com');
    }

    public function test_registration_accepts_at_prefixed_username(): void
    {
        $this->post('/register', [
            'name' => 'At User',
            'username' => '@atprefix',
            'email' => 'atprefix@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', ['username' => 'atprefix']);
    }

    public function test_login_page_shows_dev_code_after_registration(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'verifyuser',
            'email' => 'verify@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHas('dev_login_code');
        $code = $response->getSession()->get('dev_login_code');

        $this->get('/login')
            ->assertOk()
            ->assertSee($code, false);
    }

    public function test_users_can_register_without_password_using_magic_link_only(): void
    {
        $response = $this->post('/register', [
            'name' => 'Magic User',
            'username' => 'magicuser',
            'email' => 'magic@example.com',
            'magic_link_only' => '1',
        ]);

        $this->assertGuest();
        $this->assertRedirectsToRegistrationCodeStep($response, 'magic@example.com');

        $user = User::query()->where('email', 'magic@example.com')->firstOrFail();
        $this->assertFalse($user->hasPassword());
        $this->assertNull($user->getRawOriginal('password'));
    }

    public function test_password_login_rejects_passwordless_accounts(): void
    {
        User::factory()->create([
            'email' => 'magic@example.com',
            'username' => 'magicuser',
            'password' => null,
        ]);

        $this->post('/login', [
            'email' => 'magic@example.com',
            'password' => 'any-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_passwordless_user_can_set_password_from_profile(): void
    {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue($user->refresh()->hasPassword());
    }
}
