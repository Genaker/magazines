<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Magic link auth: request link → verify code → access author tools.
 */
class MagicLinkFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config(['app.debug' => true]);
    }

    public function test_magic_link_request_code_login_and_write_access(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->from(route('login'))
            ->post(route('login.magic.send'), ['email' => $user->email])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'magic-link-sent')
            ->assertSessionHas('dev_login_code');

        $code = session('dev_login_code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $code);

        $this->post(route('login.magic.verify.code'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('posts.create'))
            ->assertOk()
            ->assertSee('Write a story', false);
    }

    public function test_change_email_then_send_shows_code_step(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->withSession(['magic_login_email' => $user->email])
            ->get(route('login', ['change_email' => 1]))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('magic_login_email');

        $this->from(route('login', ['change_email' => 1]))
            ->post(route('login.magic.send'), ['email' => $user->email])
            ->assertRedirect(route('login'))
            ->assertSessionHas('magic_login_email', $user->email)
            ->assertSessionHas('dev_login_code');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('app.magic_code_intro'), false)
            ->assertSee('name="code"', false)
            ->assertDontSee(__('app.magic_login_intro'), false);
    }
}
