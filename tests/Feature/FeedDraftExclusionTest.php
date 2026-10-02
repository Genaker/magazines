<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\FeedService;
use App\Support\EntityCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedDraftExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_feed_excludes_draft_posts(): void
    {
        Post::factory()->create(['title' => 'Published story']);
        Post::factory()->draft()->create(['title' => 'Draft story']);

        $sections = app(FeedService::class)->discoverSections();

        $latestTitles = $sections['latest']->pluck('title');
        $this->assertTrue($latestTitles->contains('Published story'));
        $this->assertFalse($latestTitles->contains('Draft story'));
    }

    public function test_discover_feed_drops_post_after_it_is_demoted_to_draft(): void
    {
        $post = Post::factory()->create(['title' => 'Was published']);

        $sections = app(FeedService::class)->discoverSections();
        $this->assertTrue($sections['latest']->pluck('id')->contains($post->id));

        $post->update([
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);

        EntityCache::flushStore();

        $sections = app(FeedService::class)->discoverSections();
        $this->assertFalse($sections['latest']->pluck('id')->contains($post->id));
    }

    public function test_discover_page_does_not_render_draft_posts(): void
    {
        Post::factory()->draft()->create(['title' => 'Hidden draft in feed']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Hidden draft in feed');
    }
}
