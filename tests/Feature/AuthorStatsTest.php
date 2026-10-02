<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_stats(): void
    {
        $this->get(route('stats.index'))->assertRedirect(route('login'));
    }

    public function test_author_sees_aggregated_stats_and_per_story_table(): void
    {
        $author = User::factory()->create(['username' => 'statsauthor']);
        $follower = User::factory()->create();
        $subscriber = User::factory()->create();
        $author->followers()->attach($follower->id);
        $subscriber->storySubscriptions()->attach($author->id);

        $post = Post::factory()->for($author)->create([
            'title' => 'Stats Story Alpha',
            'slug' => 'stats-story-alpha',
            'views_count' => 42,
            'likes_count' => 7,
            'reading_time' => 5,
        ]);

        Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $follower->id,
            'body' => 'Nice stats',
        ]);

        PostView::query()->create([
            'post_id' => $post->id,
            'ip_address' => '10.0.0.1',
            'viewed_on' => now()->toDateString(),
            'created_at' => now(),
        ]);

        $list = ReadingList::defaultForUser($follower);
        $list->posts()->attach($post->id);

        $this->actingAs($author)
            ->get(route('stats.index'))
            ->assertOk()
            ->assertSee('Stats')
            ->assertSee('Monthly')
            ->assertSee('1', false)
            ->assertSee('Followers')
            ->assertSee('Email subscribers')
            ->assertSee('42')
            ->assertSee('Stats Story Alpha')
            ->assertSee('7')
            ->assertSee('1', false);
    }

    public function test_month_picker_shows_selected_month_stats(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'title' => 'Monthly Story',
            'views_count' => 10,
        ]);

        $monthStart = now('UTC')->startOfMonth();

        PostView::query()->create([
            'post_id' => $post->id,
            'ip_address' => '10.0.0.2',
            'viewed_on' => $monthStart->toDateString(),
            'created_at' => $monthStart,
        ]);

        $this->actingAs($author)
            ->get(route('stats.index', [
                'year' => $monthStart->year,
                'month' => $monthStart->month,
            ]))
            ->assertOk()
            ->assertSee('Monthly')
            ->assertSee('Monthly Story')
            ->assertSee('1', false);
    }

    public function test_viewing_past_month_without_snapshot_persists_it_automatically(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'published_at' => now('UTC')->subMonths(2),
        ]);

        $past = now('UTC')->subMonth()->startOfMonth();

        PostView::query()->create([
            'post_id' => $post->id,
            'ip_address' => '10.0.0.4',
            'viewed_on' => $past->toDateString(),
            'created_at' => $past,
        ]);

        $this->actingAs($author)
            ->get(route('stats.index', [
                'year' => $past->year,
                'month' => $past->month,
            ]))
            ->assertOk()
            ->assertSee('1', false);

        $this->assertDatabaseHas('author_monthly_stats', [
            'user_id' => $author->id,
            'year' => $past->year,
            'month' => $past->month,
            'views' => 1,
        ]);

        $this->assertDatabaseHas('post_monthly_stats', [
            'post_id' => $post->id,
            'year' => $past->year,
            'month' => $past->month,
            'views' => 1,
        ]);
    }

    public function test_snapshot_command_persists_monthly_stats(): void
    {
        $author = User::factory()->create();
        $follower = User::factory()->create();
        $author->followers()->attach($follower->id, ['created_at' => now('UTC')]);

        $post = Post::factory()->for($author)->create();
        $year = (int) now('UTC')->year;
        $month = (int) now('UTC')->month;

        PostView::query()->create([
            'post_id' => $post->id,
            'ip_address' => '10.0.0.3',
            'viewed_on' => now('UTC')->toDateString(),
            'created_at' => now('UTC'),
        ]);

        $this->artisan('stats:snapshot', [
            '--year' => $year,
            '--month' => $month,
            '--user' => $author->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('author_monthly_stats', [
            'user_id' => $author->id,
            'year' => $year,
            'month' => $month,
            'views' => 1,
            'followers_gained' => 1,
        ]);

        $this->assertDatabaseHas('post_monthly_stats', [
            'post_id' => $post->id,
            'user_id' => $author->id,
            'year' => $year,
            'month' => $month,
            'views' => 1,
        ]);
    }
}
