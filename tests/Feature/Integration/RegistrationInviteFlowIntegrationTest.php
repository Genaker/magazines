<?php

namespace Tests\Feature\Integration;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Closed registration: invite code → register → PIN verifies + login → publish.
 */
class RegistrationInviteFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('registration', false);
        Features::set('registration_invites', true);
        config(['app.debug' => true]);
    }

    public function test_invite_register_verify_and_publish(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        RegistrationInvite::query()->create([
            'code' => 'INTEGRATE1',
            'created_by' => $admin->id,
            'max_uses' => 1,
        ]);

        $response = $this->post(route('register'), [
            'name' => 'Invited Writer',
            'username' => 'invitedwriter',
            'email' => 'invited@integration.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite_code' => 'integrate1',
        ]);

        $this->assertRedirectsToRegistrationCodeStep($response, 'invited@integration.test');

        $user = User::query()->where('email', 'invited@integration.test')->firstOrFail();
        $this->assertGuest();

        $this->completeRegistrationLogin('invited@integration.test');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $category = Category::query()->create(['name' => 'Community', 'slug' => 'community']);

        $this->post(route('posts.store'), [
            'title' => 'Invite Integration Post',
            'body' => '<p>Joined via invite code.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $post = Post::query()->where('user_id', $user->id)->firstOrFail();

        $this->post(route('logout'));

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertOk()
            ->assertSee('Invite Integration Post');
    }
}
