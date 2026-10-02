<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostWithoutCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_create_post_without_category(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)
            ->post(route('posts.store'), [
                'type' => 'article',
                'title' => 'Standalone essay',
                'category_id' => '',
                'body' => '<p>No category attached.</p>',
                'status' => 'published',
                'tags' => '',
            ])
            ->assertRedirect();

        $post = Post::query()->where('title', 'Standalone essay')->first();

        $this->assertNotNull($post);
        $this->assertNull($post->category_id);
    }

    public function test_author_can_clear_category_on_update(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($author)
            ->put(route('posts.update', $post), [
                'type' => 'article',
                'title' => $post->title,
                'category_id' => '',
                'body' => $post->body,
                'status' => 'published',
                'tags' => '',
            ])
            ->assertRedirect();

        $this->assertNull($post->fresh()->category_id);
    }

    public function test_autosave_can_create_draft_without_category(): void
    {
        $author = User::factory()->create();

        $response = $this->actingAs($author)->postJson(route('posts.autosave'), [
            'title' => 'Draft without category',
        ]);

        $response->assertOk();

        $post = Post::query()->where('title', 'Draft without category')->first();

        $this->assertNotNull($post);
        $this->assertNull($post->category_id);
    }
}
