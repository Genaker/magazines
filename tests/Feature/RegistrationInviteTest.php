<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationInviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('registration', false);
        Features::set('registration_invites', true);
    }

    public function test_register_page_available_when_invites_enabled(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_register_requires_invite_code_when_open_registration_off(): void
    {
        $this->post(route('register'), [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('invite_code');

        $this->assertGuest();
    }

    public function test_user_can_register_with_valid_invite_code(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $invite = RegistrationInvite::query()->create([
            'code' => 'TESTCODE',
            'created_by' => $admin->id,
            'max_uses' => 1,
        ]);

        $response = $this->post(route('register'), [
            'name' => 'Invited User',
            'username' => 'invited',
            'email' => 'invited@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite_code' => 'testcode',
        ]);

        $this->assertRedirectsToRegistrationCodeStep($response, 'invited@example.com');

        $this->assertGuest();
        $this->assertSame(1, $invite->fresh()->uses_count);
    }

    public function test_invite_query_param_prefills_registration_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        RegistrationInvite::query()->create([
            'code' => 'LINKCODE',
            'created_by' => $admin->id,
            'max_uses' => 5,
        ]);

        $this->get(route('register', ['invite' => 'LINKCODE']))
            ->assertOk()
            ->assertSee('value="LINKCODE"', false);
    }

    public function test_register_returns_not_found_when_both_modes_disabled(): void
    {
        Features::set('registration_invites', false);

        $this->get(route('register'))->assertNotFound();
    }

    public function test_super_admin_can_create_and_revoke_invite(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->post(route('admin.settings.invites.store'), [
                'max_uses' => 3,
                'expires_in_days' => 7,
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('invite_code');

        $invite = RegistrationInvite::query()->first();
        $this->assertNotNull($invite);
        $this->assertSame(3, $invite->max_uses);

        $this->actingAs($admin)
            ->delete(route('admin.settings.invites.destroy', $invite))
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertNotNull($invite->fresh()->revoked_at);
    }
}
