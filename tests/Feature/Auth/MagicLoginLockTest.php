<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Features;
use App\Support\MagicLoginLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagicLoginLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config(['magic-login.max_failed_attempts' => 3]);
    }

    public function test_failed_magic_code_attempts_are_counted_and_lock_account(): void
    {
        config(['app.debug' => true]);

        $user = User::factory()->create([
            'email' => 'lockme@example.com',
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.magic.send'), ['email' => $user->email])
            ->assertSessionHas('dev_login_code');

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('login.magic.verify.code'), [
                'email' => $user->email,
                'code' => '000000',
            ])->assertSessionHasErrors('code');

            $this->assertSame($i + 1, $user->fresh()->magic_login_failed_attempts);
            $this->assertNull($user->fresh()->magic_login_locked_at);
        }

        $this->post(route('login.magic.verify.code'), [
            'email' => $user->email,
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertTrue(MagicLoginLock::isLocked($user));
        $this->assertSame(3, $user->magic_login_failed_attempts);
    }

    public function test_locked_user_cannot_request_magic_link(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@example.com',
            'email_verified_at' => now(),
            'magic_login_failed_attempts' => 5,
            'magic_login_locked_at' => now(),
        ]);

        $this->from(route('login'))
            ->post(route('login.magic.send'), ['email' => $user->email])
            ->assertSessionHasErrors('email');
    }

    public function test_successful_magic_login_clears_failed_attempts(): void
    {
        config(['app.debug' => true]);

        $user = User::factory()->create([
            'email' => 'clear@example.com',
            'email_verified_at' => now(),
            'magic_login_failed_attempts' => 2,
        ]);

        $this->post(route('login.magic.send'), ['email' => $user->email]);
        $code = session('dev_login_code');

        $this->post(route('login.magic.verify.code'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('home'));

        $user->refresh();
        $this->assertSame(0, $user->magic_login_failed_attempts);
        $this->assertNull($user->magic_login_locked_at);
    }

    public function test_unlock_command_clears_magic_login_lock(): void
    {
        $user = User::factory()->create([
            'email' => 'unlock@example.com',
            'magic_login_failed_attempts' => 5,
            'magic_login_locked_at' => now(),
        ]);

        $this->artisan('app:user:unlock-magic-login', ['email' => 'unlock@example.com'])
            ->assertSuccessful();

        $user->refresh();
        $this->assertFalse(MagicLoginLock::isLocked($user));
        $this->assertSame(0, $user->magic_login_failed_attempts);
    }
}
