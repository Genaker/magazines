<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered_for_guest(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->withSession(['pending_verification_email' => $user->email])
            ->get('/verify-email');

        $response->assertOk();
        $response->assertSee($user->email, false);
    }

    public function test_email_can_be_verified_without_being_logged_in(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('login'));
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->get($verificationUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_notification_get_redirects_to_notice_page(): void
    {
        $this->get('/email/verification-notification')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_authenticated_user_can_resend_verification_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_guest_can_resend_verification_email_with_pending_session_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->withSession(['pending_verification_email' => $user->email])
            ->post('/email/verification-notification')
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_guest_can_resend_verification_email_with_email_in_form(): void
    {
        $user = User::factory()->unverified()->create();

        $this->post('/email/verification-notification', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_verify_page_accepts_email_query_param_for_guest(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get('/verify-email?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee($user->email, false)
            ->assertSessionHas('pending_verification_email', $user->email);
    }

    public function test_verify_page_does_not_store_unknown_email_from_query(): void
    {
        $this->get('/verify-email?email='.urlencode('nobody@example.com'))
            ->assertOk()
            ->assertSee('nobody@example.com', false)
            ->assertSessionMissing('pending_verification_email');
    }

    public function test_verify_page_redirects_when_query_email_already_verified(): void
    {
        $user = User::factory()->create();

        $this->get('/verify-email?email='.urlencode($user->email))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');
    }

    public function test_verify_page_clears_stale_session_email_when_user_missing(): void
    {
        $this->withSession(['pending_verification_email' => 'ghost@example.com'])
            ->get('/verify-email')
            ->assertOk()
            ->assertSessionMissing('pending_verification_email');
    }
}
