<?php

namespace Tests\Feature;

use App\Models\AuthorAlias;
use App\Models\Post;
use App\Models\User;
use App\Support\ViewHooks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewHooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_adds_view_to_hook_slot(): void
    {
        ViewHooks::register('test.slot', 'partials.hooks.post-banner');

        $this->assertSame(
            ['partials.hooks.post-banner'],
            ViewHooks::views('test.slot'),
        );
    }

    public function test_registered_hook_renders_on_post_page(): void
    {
        ViewHooks::register('post.after_content', 'partials.hooks.post-banner');

        $author = User::factory()->create();
        $alias = AuthorAlias::factory()->for($author)->create(['is_primary' => true]);
        $post = Post::factory()->for($author)->create([
            'author_alias_id' => $alias->id,
            'title' => 'Hook Test Story',
            'status' => 'published',
        ]);

        $this->get(route('posts.show', [$alias, $post->slug]))
            ->assertOk()
            ->assertSee('Hook Test Story banner', false);
    }

    public function test_hook_receives_data_from_template(): void
    {
        ViewHooks::register('post.before_content', 'partials.hooks.post-banner');

        $author = User::factory()->create();
        $alias = AuthorAlias::factory()->for($author)->create(['is_primary' => true]);
        $post = Post::factory()->for($author)->create([
            'author_alias_id' => $alias->id,
            'title' => 'Before Content Hook',
            'status' => 'published',
        ]);

        $response = $this->get(route('posts.show', [$alias, $post->slug]));

        $response->assertOk();
        $this->assertTrue(
            str_contains($response->getContent(), 'Before Content Hook banner'),
        );
    }
}
