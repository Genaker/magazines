<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_avatar_download_generates_initials_image_when_no_upload(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $user = User::factory()->create(['username' => 'demoauthor']);

        $response = $this->actingAs($user)->get('/profile/avatar');

        $response->assertOk();
        $response->assertHeader('content-type', 'image/jpeg');
        $response->assertDownload('demoauthor-avatar.jpg');
        $this->assertStringStartsWith("\xFF\xD8", $response->getContent() ?: '');
    }

    public function test_profile_avatar_download_serves_uploaded_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['username' => 'demoauthor']);
        $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        $response = $this->actingAs($user)->get('/profile/avatar');

        $response->assertOk();
        $response->assertDownload('demoauthor-avatar.jpg');
    }

    public function test_public_author_avatar_download_is_available(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $user = User::factory()->create(['username' => 'demoauthor']);

        $response = $this->get('/@demoauthor/avatar');

        $response->assertOk();
        $response->assertDownload('demoauthor-avatar.jpg');
    }

    public function test_profile_page_shows_initials_avatar_when_no_upload(): void
    {
        $user = User::factory()->create(['username' => 'demoauthor']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('DE', false);
        $response->assertDontSee('Download avatar image', false);
    }

    public function test_profile_update_resizes_uploaded_avatar_to_configured_size(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        Storage::fake('public');

        $user = User::factory()->create(['username' => 'demoauthor']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('large.jpg', 800, 600),
            ])
            ->assertRedirect(route('profile.edit'));

        $path = $user->fresh()->avatar;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame((int) config('media.avatar.size', 100), $width);
        $this->assertSame((int) config('media.avatar.size', 100), $height);
    }

    public function test_profile_avatar_can_be_removed(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['username' => 'removeavatar']);
        $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars', 'public');
        $user->update(['avatar' => $path]);
        $user->primaryAlias()?->update(['avatar' => $path]);

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->avatar);
        $this->assertNull($user->primaryAlias()?->avatar);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_profile_page_shows_remove_avatar_when_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['username' => 'hasavatar']);
        $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Remove avatar', false)
            ->assertSee('Upload avatar image', false)
            ->assertDontSee('Download avatar image', false)
            ->assertDontSee('Choose File', false);
    }
}
