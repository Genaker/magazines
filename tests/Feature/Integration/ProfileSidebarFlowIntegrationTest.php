<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Notifications\UserActivity;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Own-profile sidebar: upload link, action buttons, self-follow, avatar replace.
 */
class ProfileSidebarFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_own_profile_sidebar_shows_upload_link_and_action_buttons(): void
    {
        $user = User::factory()->create(['username' => 'sidebaruser', 'name' => 'Sidebar User']);

        $this->actingAs($user)
            ->get(route('authors.show', $user->primaryAlias()))
            ->assertOk()
            ->assertSee('Upload avatar image', false)
            ->assertSee(__('app.edit_profile'), false)
            ->assertSee(__('app.my_stories'), false)
            ->assertSee(__('app.stats'), false)
            ->assertSee('id="follow-btn"', false);
    }

    public function test_self_follow_on_own_profile_does_not_notify(): void
    {
        Notification::fake();

        $user = User::factory()->create(['username' => 'selfnotify']);

        $this->actingAs($user)
            ->postJson(route('users.follow', $user))
            ->assertOk()
            ->assertJson(['following' => true]);

        Notification::assertNothingSent();
    }

    public function test_avatar_upload_from_author_profile_returns_to_profile(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        Storage::fake('public');

        $user = User::factory()->create(['username' => 'avatarback']);
        $profileUrl = route('authors.show', $user->primaryAlias());

        $this->actingAs($user)
            ->from($profileUrl)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 640, 480),
            ])
            ->assertRedirect($profileUrl);

        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_avatar_upload_replaces_previous_file(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        Storage::fake('public');

        $user = User::factory()->create(['username' => 'replaceavatar']);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('avatars', 'public');
        $user->update(['avatar' => $oldPath]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('new.jpg', 700, 700),
            ])
            ->assertRedirect(route('profile.edit'));

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }
}
