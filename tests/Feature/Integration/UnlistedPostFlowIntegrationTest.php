<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\User;
use App\Support\EntityCache;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unlisted posts: link works for guests but excluded from home and search.
 */
class UnlistedPostFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        EntityCache::flushStore();
    }

    public function test_unlisted_post_accessible_by_link_but_not_discoverable(): void
    {
        $author = User::factory()->create(['username' => 'unlistedauthor']);
        $category = Category::query()->create(['name' => 'Notes', 'slug' => 'notes']);

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Unlisted Integration Gem',
            'body' => '<p>UniqueUnlistedTerm hidden from feeds.</p>',
            'category_id' => $category->id,
            'status' => 'unlisted',
        ])->assertRedirect();

        $showUrl = route('posts.show', [$author->primaryAlias(), 'unlisted-integration-gem']);

        $this->post(route('logout'));

        $this->get($showUrl)
            ->assertOk()
            ->assertSee('Unlisted Integration Gem');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Unlisted Integration Gem');

        $this->get(route('search', ['q' => 'UniqueUnlistedTerm']))
            ->assertOk()
            ->assertDontSee('Unlisted Integration Gem');
    }
}
