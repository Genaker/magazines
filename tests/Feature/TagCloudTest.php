<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagCloudTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_popular_tags_ranked_by_published_post_count(): void
    {
        $php = Tag::query()->create(['name' => 'php', 'slug' => 'php']);
        $laravel = Tag::query()->create(['name' => 'laravel', 'slug' => 'laravel']);
        $writing = Tag::query()->create(['name' => 'writing', 'slug' => 'writing']);

        Post::factory()->count(3)->create()->each(fn (Post $post) => $post->tags()->sync([$php->id]));
        Post::factory()->count(2)->create()->each(fn (Post $post) => $post->tags()->sync([$laravel->id]));
        Post::factory()->create()->tags()->sync([$writing->id]);

        Post::factory()->draft()->create()->tags()->sync([$laravel->id]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee(__('app.popular_tags'), false);
        $response->assertSeeInOrder([
            '#php',
            '(3)',
            '#laravel',
            '(2)',
            '#writing',
            '(1)',
        ], false);
        $response->assertDontSee('(3 posts)', false);
    }

    public function test_home_hides_tag_cloud_when_no_published_posts_have_tags(): void
    {
        Tag::query()->create(['name' => 'empty', 'slug' => 'empty']);
        Post::factory()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('app.popular_tags'), false);
    }
}
