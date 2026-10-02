<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_middleware_includes_verify_csrf_token(): void
    {
        $group = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'];

        $this->assertContains(VerifyCsrfToken::class, $group);
    }

    public function test_post_with_session_csrf_token_is_allowed(): void
    {
        $post = Post::factory()->create();

        $this->get(route('home'));

        $this->withHeader('X-CSRF-TOKEN', session()->token())
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true]);
    }
}
