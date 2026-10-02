<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorProfileStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_profile_shows_article_follower_and_following_counts(): void
    {
        $author = User::factory()->create(['username' => 'statsauthor']);
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        Post::factory()->for($author)->count(2)->create();
        Post::factory()->for($author)->draft()->create();

        $author->followers()->attach($follower);
        $author->following()->attach($followed);

        $response = $this->get(route('authors.show', $author));

        $response->assertOk();
        $response->assertSee('2 stories');
        $response->assertSee('1 follower');
        $response->assertSee(__('app.home'));
        $response->assertSee(__('app.about'));
    }

    public function test_author_profile_lists_posts_newest_first(): void
    {
        $author = User::factory()->create(['username' => 'orderauthor']);
        Post::factory()->for($author)->create([
            'title' => 'Older Story',
            'slug' => 'older-story',
            'published_at' => now()->subDays(5),
        ]);
        Post::factory()->for($author)->create([
            'title' => 'Newer Story',
            'slug' => 'newer-story',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('authors.show', $author));
        $response->assertOk();

        $newerPos = strpos($response->getContent(), 'Newer Story');
        $olderPos = strpos($response->getContent(), 'Older Story');
        $this->assertNotFalse($newerPos);
        $this->assertNotFalse($olderPos);
        $this->assertLessThan($olderPos, $newerPos);
    }

    public function test_author_profile_about_tab_shows_bio_and_stats(): void
    {
        $author = User::factory()->create([
            'username' => 'aboutauthor',
            'bio' => 'Writes about testing.',
        ]);
        $author->primaryAlias()->update(['bio' => 'Writes about testing.']);

        $this->get(route('authors.show', $author).'?tab=about')
            ->assertOk()
            ->assertSee('Writes about testing.')
            ->assertSee(__('app.stats'));
    }

    public function test_own_author_profile_shows_follow_button_and_allows_self_follow(): void
    {
        $user = User::factory()->create(['username' => 'selffollower']);

        $this->actingAs($user)
            ->get(route('authors.show', $user->primaryAlias()))
            ->assertOk()
            ->assertSee('id="follow-btn"', false);

        $this->actingAs($user)
            ->postJson(route('users.follow', $user))
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->assertTrue($user->following()->where('following_id', $user->id)->exists());
    }
}
