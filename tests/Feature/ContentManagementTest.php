<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\LoginLink;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_syncs_up_to_ten_tags(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = \App\Models\Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $tags = implode(', ', range(1, 12));

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Tagged story',
            'body' => '<p>Body</p>',
            'category_id' => $category->id,
            'status' => 'draft',
            'tags' => $tags,
        ])->assertRedirect();

        $post = Post::query()->first();
        $this->assertCount(10, $post->tags);
    }

    public function test_magic_link_request_creates_login_link(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->post(route('login.magic.send'), ['email' => $user->email]);

        $response->assertRedirect();
        $this->assertDatabaseHas('login_links', ['email' => $user->email]);
    }

    public function test_magic_link_verification_logs_user_in(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = 'plain-token';

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->get(route('login.magic.verify', ['token' => $token, 'email' => $user->email]));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_magic_code_verification_logs_user_in(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $code = '123456';

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', 'unused-token'),
            'code' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post(route('login.magic.verify.code'), [
            'email' => $user->email,
            'code' => $code,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_magic_code_cannot_be_reused_after_link_login(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = 'plain-token';
        $code = '654321';

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', $token),
            'code' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->get(route('login.magic.verify', ['token' => $token, 'email' => $user->email]))
            ->assertRedirect(route('home'));

        $this->post(route('logout'));

        $this->post(route('login.magic.verify.code'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_expired_magic_code_keeps_code_step_and_resend_sends_new_link(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $code = '123456';

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', 'unused-token'),
            'code' => hash('sha256', $code),
            'expires_at' => now()->subMinute(),
        ]);

        $this->withSession(['magic_login_email' => $user->email])
            ->post(route('login.magic.verify.code'), [
                'email' => $user->email,
                'code' => $code,
            ])
            ->assertSessionHasErrors('code')
            ->assertSessionHas('magic_login_email', $user->email);

        $countBefore = LoginLink::query()->where('email', $user->email)->count();

        $this->withSession(['magic_login_email' => $user->email])
            ->from(route('login'))
            ->post(route('login.magic.send'), ['email' => $user->email])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'magic-link-resent')
            ->assertSessionHas('magic_login_email', $user->email);

        $this->assertSame($countBefore + 1, LoginLink::query()->where('email', $user->email)->count());
    }

    public function test_invalid_magic_link_redirects_to_code_step_with_resend(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', 'expired-token'),
            'code' => hash('sha256', '999888'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get(route('login.magic.verify', ['token' => 'expired-token', 'email' => $user->email]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('code')
            ->assertSessionHas('magic_login_email', $user->email);

        $this->assertGuest();
    }

    public function test_post_show_includes_open_graph_meta(): void
    {
        $user = User::factory()->create(['username' => 'writer']);
        $category = \App\Models\Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $post = Post::factory()->for($user)->create([
            'category_id' => $category->id,
            'title' => 'OG Story',
            'subtitle' => 'A subtitle for social',
            'slug' => 'og-story',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('posts.show', [$user->primaryAlias(), $post->slug]));

        $response->assertOk();
        $response->assertSee('property="og:title" content="OG Story"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('A subtitle for social', false);
    }

    public function test_magazine_editor_can_approve_submission(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $category = \App\Models\Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);

        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $post = Post::factory()->for($writer)->create([
            'category_id' => $category->id,
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => 'pending',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($owner)->post(route('magazines.submissions.approve', [$magazine, $post]));

        $response->assertRedirect();
        $post->refresh();
        $this->assertSame('approved', $post->magazine_submission_status->value);
        $this->assertSame('published', $post->status->value);
    }

    public function test_magazine_invite_accepts_at_prefixed_username(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create([
            'email_verified_at' => now(),
            'username' => 'invitedwriter',
        ]);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);

        $response = $this->actingAs($owner)->post(route('magazines.members.invite', $magazine), [
            'username' => '@invitedwriter',
            'role' => 'writer',
        ]);

        $response->assertRedirect();
        $this->assertTrue($magazine->members()->where('users.id', $writer->id)->exists());
    }

    public function test_manage_magazine_page_lists_members(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'username' => 'magowner',
            'name' => 'Mag Owner',
        ]);
        $writer = User::factory()->create([
            'email_verified_at' => now(),
            'username' => 'magwriter',
            'name' => 'Mag Writer',
        ]);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);
        $magazine->members()->attach($owner->id, ['role' => 'owner']);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $response = $this->actingAs($owner)->get(route('magazines.submissions', $magazine));

        $response->assertOk();
        $response->assertSee('Mag Owner', false);
        $response->assertSee('@magwriter', false);
        $response->assertSee('writer', false);
    }

    public function test_media_upload_stores_processed_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $file = UploadedFile::fake()->image('cover.jpg', 1600, 900);

        $response = $this->actingAs($user)->post(route('media.upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJsonStructure(['location']);
    }

    public function test_post_show_renders_image_gallery_component(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'username' => 'galleryauthor',
        ]);
        $category = Category::query()->create(['name' => 'Photos', 'slug' => 'photos']);
        $galleryBody = '<div class="post-gallery" data-component="gallery">'
            .'<a href="/storage/media/editor/a.jpg"><img src="/storage/media/editor/a.jpg" alt="Gallery image 1"></a>'
            .'<a href="/storage/media/editor/b.jpg"><img src="/storage/media/editor/b.jpg" alt="Gallery image 2"></a>'
            .'</div>';

        $post = Post::query()->create([
            'user_id' => $user->id,
            'author_alias_id' => $user->primaryAlias()->id,
            'category_id' => $category->id,
            'title' => 'Gallery story',
            'slug' => 'gallery-story',
            'body' => $galleryBody,
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->get(route('posts.show', [$user->primaryAlias(), $post->slug]));

        $response->assertOk();
        $response->assertSee('data-component="gallery"', false);
        $response->assertSee('class="post-gallery"', false);
        $response->assertSee('Gallery image 1', false);
    }

    public function test_post_show_renders_embedded_video_iframe(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'username' => 'videoauthor',
        ]);
        $category = Category::query()->create(['name' => 'Video', 'slug' => 'video']);
        $body = '<p>Watch this:</p><iframe width="560" height="314" src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="YouTube video" allowfullscreen></iframe>';

        $post = Post::query()->create([
            'user_id' => $user->id,
            'author_alias_id' => $user->primaryAlias()->id,
            'category_id' => $category->id,
            'title' => 'Video story',
            'slug' => 'video-story',
            'body' => $body,
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->assertStringNotContainsString('width=', $post->fresh()->body);
        $this->assertStringNotContainsString('height=', $post->fresh()->body);

        $response = $this->get(route('posts.show', [$user->primaryAlias(), $post->slug]));

        $response->assertOk();
        $response->assertSee('youtube.com/embed/dQw4w9WgXcQ', false);
        $response->assertSee('<iframe', false);
    }
}
