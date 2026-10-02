<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\EntityCache;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end publishing: write → publish → discover on home, search, tag page, and RSS.
 */
class PublishingFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        EntityCache::flushStore();
    }

    public function test_author_publish_to_guest_discovery_and_syndication(): void
    {
        $author = User::factory()->create(['username' => 'flowauthor']);
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays']);

        $this->actingAs($author)
            ->post(route('posts.store'), [
                'title' => 'Integration Published Story',
                'subtitle' => 'A cross-feature smoke test',
                'body' => '<p>UniqueSearchTerm integration body.</p>',
                'category_id' => $category->id,
                'status' => 'published',
                'tags' => 'integration, publishing',
            ])
            ->assertRedirect();

        $post = Post::query()->where('user_id', $author->id)->firstOrFail();
        $showUrl = route('posts.show', [$author->primaryAlias(), $post->slug]);

        $this->post(route('logout'));

        $this->get($showUrl)
            ->assertOk()
            ->assertSee('Integration Published Story')
            ->assertSee('UniqueSearchTerm integration body', false);

        $this->get(route('search', ['q' => 'UniqueSearchTerm']))
            ->assertOk()
            ->assertSee('Integration Published Story');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Integration Published Story');

        $this->get(route('tags.show', Tag::query()->where('slug', 'integration')->firstOrFail()))
            ->assertOk()
            ->assertSee('Integration Published Story');

        $this->get(route('syndication.rss'))
            ->assertOk()
            ->assertSee('Integration Published Story', false);

        $this->get(route('authors.show', $author->primaryAlias()))
            ->assertOk()
            ->assertSee('Integration Published Story');
    }

    public function test_author_publishes_without_category_and_guest_can_read_on_home(): void
    {
        $author = User::factory()->create(['username' => 'nocategoryauthor']);

        $this->actingAs($author)
            ->post(route('posts.store'), [
                'title' => 'Uncategorized Integration Story',
                'body' => '<p>No category attached in integration flow.</p>',
                'category_id' => '',
                'status' => 'published',
            ])
            ->assertRedirect();

        $post = Post::query()->where('user_id', $author->id)->firstOrFail();
        $this->assertNull($post->category_id);

        $this->post(route('logout'));

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('Uncategorized Integration Story');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Uncategorized Integration Story');

        $this->get(route('authors.show', $author->primaryAlias()))
            ->assertOk()
            ->assertSee('Uncategorized Integration Story');
    }
}
