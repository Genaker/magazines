<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Avatar initials display and download endpoints.
 */
class AvatarFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_guest_can_download_generated_avatar_for_public_alias(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $user = User::factory()->create(['username' => 'avatarguest']);

        $response = $this->get(route('authors.avatar', $user->primaryAlias()));

        $response->assertOk();
        $response->assertDownload('avatarguest-avatar.jpg');
    }

    public function test_logged_in_user_can_download_own_avatar_image(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $user = User::factory()->create(['username' => 'avatarowner']);

        $this->actingAs($user)
            ->get(route('profile.avatar'))
            ->assertOk()
            ->assertDownload('avatarowner-avatar.jpg');
    }

    public function test_author_profile_shows_initials_when_no_upload(): void
    {
        $user = User::factory()->create(['username' => 'initialsuser']);

        $this->get(route('authors.show', $user->primaryAlias()))
            ->assertOk()
            ->assertSee('IN', false);
    }
}
