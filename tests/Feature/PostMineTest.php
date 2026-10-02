<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostMineTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_view_their_drafts_on_my_stories_page(): void
    {
        $user = User::factory()->create();
        $draft = Post::factory()->for($user)->draft()->create(['title' => 'Secret draft']);
        Post::factory()->create(['title' => 'Someone else post']);

        $response = $this->actingAs($user)->get(route('posts.mine'));

        $response->assertOk();
        $response->assertSee('Secret draft');
        $response->assertSee('draft');
        $response->assertDontSee('Someone else post');
    }

    public function test_unverified_user_cannot_access_my_stories_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('posts.mine'))
            ->assertRedirect(route('verification.notice'));
    }
}
