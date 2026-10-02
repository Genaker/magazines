<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reading_time_is_calculated_on_save(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $words = implode(' ', array_fill(0, 400, 'word'));

        $post = Post::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Long Post',
            'slug' => 'long-post',
            'body' => $words,
            'status' => 'draft',
        ]);

        $this->assertGreaterThanOrEqual(2, $post->reading_time);
    }
}
