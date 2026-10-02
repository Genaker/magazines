<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API token auth → create post → same story visible on the public web.
 */
class ApiPublishingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_api_created_post_is_visible_on_web(): void
    {
        $author = User::factory()->create(['username' => 'apiwriter']);
        $category = Category::query()->create(['name' => 'API', 'slug' => 'api-cat']);
        $token = $author->createToken('integration')->plainTextToken;

        $this->postJson('/api/v1/posts', [
            'title' => 'API Integration Story',
            'body' => '<p>Created via Sanctum token.</p>',
            'category_id' => $category->id,
            'status' => 'published',
            'tags' => ['api', 'integration'],
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'API Integration Story');

        $post = Post::query()->where('user_id', $author->id)->firstOrFail();

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('API Integration Story');

        Sanctum::actingAs($author);

        $this->get(route('posts.mine'))
            ->assertOk()
            ->assertSee('API Integration Story');
    }
}
