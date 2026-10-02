<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_suggest_returns_matching_tags_ordered_by_usage(): void
    {
        $popular = Tag::query()->create(['name' => 'laravel', 'slug' => 'laravel']);
        $other = Tag::query()->create(['name' => 'language', 'slug' => 'language']);

        Post::factory()->count(2)->create()->each(fn (Post $post) => $post->tags()->attach($popular));
        Post::factory()->create()->tags()->attach($other);

        $response = $this->getJson(route('tags.suggest', ['q' => 'lar']));

        $response->assertOk()->assertJson(['laravel']);
        $this->assertSame('laravel', $response->json()[0]);
    }

    public function test_tag_suggest_returns_empty_for_blank_query(): void
    {
        Tag::query()->create(['name' => 'writing', 'slug' => 'writing']);

        $this->getJson(route('tags.suggest', ['q' => '']))
            ->assertOk()
            ->assertJson([]);
    }
}
