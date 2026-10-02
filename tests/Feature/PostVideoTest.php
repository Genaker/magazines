<?php

namespace Tests\Feature;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_publish_video_post(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Video', 'slug' => 'video']);

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'type' => PostType::Video->value,
            'title' => 'Talk on design',
            'category_id' => $category->id,
            'status' => 'published',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'body' => '<p>Notes from the session.</p>',
        ]);

        $post = Post::query()->where('title', 'Talk on design')->firstOrFail();
        $response->assertRedirect(route('posts.show', [$post->authorAlias, $post->slug]));

        $this->assertSame(PostType::Video, $post->type);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $post->video_url);

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertOk()
            ->assertSee('post-video-embed', false)
            ->assertSee('youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Notes from the session.', false);
    }

    public function test_video_embed_appears_before_body_on_show_page(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Video', 'slug' => 'video']);

        $post = Post::factory()->for($user)->create([
            'category_id' => $category->id,
            'type' => PostType::Video,
            'video_url' => 'https://vimeo.com/123456789',
            'body' => '<p>Description below the player.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post->load('authorAlias');

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertOk()
            ->assertSeeInOrder([
                'player.vimeo.com/video/123456789',
                'Description below the player.',
            ], false);
    }

    public function test_published_video_requires_supported_url(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Video', 'slug' => 'video']);

        $this->actingAs($user)
            ->from(route('posts.create.video'))
            ->post(route('posts.store'), [
                'type' => PostType::Video->value,
                'title' => 'Bad link',
                'category_id' => $category->id,
                'status' => 'published',
                'video_url' => 'https://example.com/not-a-provider',
            ])
            ->assertRedirect(route('posts.create.video'))
            ->assertSessionHasErrors('video_url');

        $this->assertDatabaseMissing('posts', ['title' => 'Bad link']);
    }

    public function test_video_create_page_is_available_to_authors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('posts.create.video'))
            ->assertOk()
            ->assertSee(__('app.create_video'), false);
    }
}
