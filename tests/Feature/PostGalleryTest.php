<?php

namespace Tests\Feature;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PostGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_publish_gallery_post_with_photos(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Photo', 'slug' => 'photo']);

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'type' => PostType::Gallery->value,
            'title' => 'Street Photography',
            'category_id' => $category->id,
            'status' => 'published',
            'gallery_images' => [
                UploadedFile::fake()->image('one.jpg', 900, 600),
                UploadedFile::fake()->image('two.jpg', 900, 600),
            ],
            'gallery_captions' => [
                'Sunset over the river',
                'Bridge at dusk',
            ],
        ]);

        $post = Post::query()->where('title', 'Street Photography')->firstOrFail();
        $response->assertRedirect(route('posts.show', [$post->authorAlias, $post->slug]));

        $this->assertSame(PostType::Gallery, $post->type);
        $this->assertSame(2, $post->galleryItems()->count());
        $this->assertNotNull($post->cover_image);

        $items = $post->galleryItems()->orderBy('sort_order')->get();
        $this->assertSame('Sunset over the river', $items[0]->caption);
        $this->assertSame('Bridge at dusk', $items[1]->caption);

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertOk()
            ->assertSee('portfolio-gallery', false)
            ->assertSee('data-component="gallery"', false)
            ->assertSee('alt="Sunset over the river"', false)
            ->assertSee('Sunset over the river', false);
    }

    public function test_gallery_post_can_include_rich_text_body(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Photo', 'slug' => 'photo']);

        $this->actingAs($user)->post(route('posts.store'), [
            'type' => PostType::Gallery->value,
            'title' => 'With Story',
            'category_id' => $category->id,
            'status' => 'published',
            'body' => '<p>Notes from the field.</p>',
            'gallery_images' => [
                UploadedFile::fake()->image('one.jpg', 900, 600),
                UploadedFile::fake()->image('two.jpg', 900, 600),
            ],
        ])->assertRedirect();

        $post = Post::query()->where('title', 'With Story')->firstOrFail();

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertOk()
            ->assertSee('Notes from the field.', false)
            ->assertSee('min read', false);
    }

    public function test_published_gallery_requires_minimum_photo_count(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Photo', 'slug' => 'photo']);

        $this->actingAs($user)
            ->from(route('posts.create.gallery'))
            ->post(route('posts.store'), [
                'type' => PostType::Gallery->value,
                'title' => 'Too Small',
                'category_id' => $category->id,
                'status' => 'published',
                'gallery_images' => [
                    UploadedFile::fake()->image('one.jpg', 800, 600),
                ],
            ])
            ->assertSessionHasErrors('gallery_images');

        $this->assertDatabaseMissing('posts', ['title' => 'Too Small']);
    }

    public function test_gallery_create_page_is_available_to_authors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('posts.create.gallery'))
            ->assertOk()
            ->assertSee(__('app.create_gallery'), false);
    }
}
