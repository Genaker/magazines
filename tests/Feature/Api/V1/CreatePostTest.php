<?php

namespace Tests\Feature\Api\V1;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreatePostTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_post_via_api(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'API', 'slug' => 'api']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'API Story',
            'subtitle' => 'From the mobile app',
            'body' => '<p>Hello API readers.</p>',
            'category_id' => $category->id,
            'status' => 'published',
            'tags' => ['api', 'news'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'API Story')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.type', 'article')
            ->assertJsonStructure(['data' => ['id', 'url', 'tags']]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'API Story',
            'status' => PostStatus::Published->value,
        ]);
    }

    public function test_bearer_token_can_create_post(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'API', 'slug' => 'api']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Token Post',
            'body' => '<p>Created with token.</p>',
            'category_id' => $category->id,
            'status' => 'draft',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Token Post')
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_guest_cannot_create_post(): void
    {
        $category = Category::query()->create(['name' => 'API', 'slug' => 'api']);

        $this->postJson('/api/v1/posts', [
            'title' => 'Nope',
            'body' => '<p>Nope</p>',
            'category_id' => $category->id,
            'status' => 'draft',
        ])->assertUnauthorized();
    }

    public function test_unverified_user_cannot_create_post(): void
    {
        $user = User::factory()->unverified()->create();
        $category = Category::query()->create(['name' => 'API', 'slug' => 'api']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/posts', [
            'title' => 'Blocked',
            'body' => '<p>Blocked</p>',
            'category_id' => $category->id,
            'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_banned_user_cannot_create_post(): void
    {
        $user = User::factory()->create(['is_banned' => true]);
        $category = Category::query()->create(['name' => 'API', 'slug' => 'api']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/posts', [
            'title' => 'Banned',
            'body' => '<p>Banned</p>',
            'category_id' => $category->id,
            'status' => 'draft',
        ])->assertForbidden();
    }
}
