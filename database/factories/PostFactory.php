<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'user_id' => User::factory(),
            'category_id' => fn () => $this->categoryId(),
            'title' => $title,
            'subtitle' => fake()->optional()->sentence(),
            'slug' => Slugger::unique($title, new Post, 'slug'),
            'type' => \App\Enums\PostType::Article,
            'body' => '<p>'.fake()->paragraph().'</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ];
    }

    public function gallery(): static
    {
        return $this->state(fn () => [
            'type' => \App\Enums\PostType::Gallery,
            'body' => '',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'type' => \App\Enums\PostType::Video,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'body' => '<p>'.fake()->paragraph().'</p>',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function unlisted(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Unlisted,
            'published_at' => now(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Post $post): void {
            if ($post->author_alias_id) {
                return;
            }

            $alias = AuthorAlias::query()
                ->where('user_id', $post->user_id)
                ->where('is_primary', true)
                ->first();

            if (! $alias) {
                $user = User::query()->find($post->user_id);
                $alias = $user ? AuthorAlias::createFromUser($user, isPrimary: true) : null;
            }

            if ($alias) {
                $post->update(['author_alias_id' => $alias->id]);
            }
        });
    }

    private function categoryId(): int
    {
        $category = Category::query()->first();

        if (! $category) {
            $category = Category::query()->create([
                'name' => 'General',
                'slug' => 'general',
            ]);
        }

        return $category->id;
    }
}
