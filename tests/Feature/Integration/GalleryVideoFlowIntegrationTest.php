<?php

namespace Tests\Feature\Integration;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Gallery and video posts: publish via web → guest views embedded media.
 */
class GalleryVideoFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_gallery_publish_to_public_show_page(): void
    {
        $author = User::factory()->create(['username' => 'galleryflow']);
        $category = Category::query()->create(['name' => 'Photo', 'slug' => 'photo']);

        $this->actingAs($author)->post(route('posts.store'), [
            'type' => PostType::Gallery->value,
            'title' => 'Integration Gallery',
            'body' => '<p>Field notes from the shoot.</p>',
            'category_id' => $category->id,
            'status' => 'published',
            'gallery_images' => [
                UploadedFile::fake()->image('a.jpg', 900, 600),
                UploadedFile::fake()->image('b.jpg', 900, 600),
            ],
            'gallery_captions' => ['First frame', 'Second frame'],
        ])->assertRedirect();

        $post = Post::query()->where('title', 'Integration Gallery')->firstOrFail();

        $this->post(route('logout'));

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('data-component="gallery"', false)
            ->assertSee('Field notes from the shoot.', false)
            ->assertSee('First frame', false);
    }

    public function test_video_publish_to_public_embed_page(): void
    {
        $author = User::factory()->create(['username' => 'videoflow']);
        $category = Category::query()->create(['name' => 'Video', 'slug' => 'video']);

        $this->actingAs($author)->post(route('posts.store'), [
            'type' => PostType::Video->value,
            'title' => 'Integration Video Talk',
            'category_id' => $category->id,
            'status' => 'published',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'body' => '<p>Session recap below the player.</p>',
        ])->assertRedirect();

        $post = Post::query()->where('title', 'Integration Video Talk')->firstOrFail();

        $this->post(route('logout'));

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('post-video-embed', false)
            ->assertSee('youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Session recap below the player.', false);
    }
}
