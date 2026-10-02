<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_seo_prefers_share_image_over_cover(): void
    {
        $user = User::factory()->create();
        $alias = $user->primaryAlias();
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'cover_image' => 'media/covers/cover.jpg',
            'share_image' => 'media/share-images/share.jpg',
        ]);

        $seo = Seo::forPost($post->load(['authorAlias', 'user', 'category']));

        $this->assertStringContainsString('share-images/share.jpg', (string) $seo['image']);
        $this->assertSame('article', $seo['type']);
        $this->assertSame('News', $seo['section']);
        $this->assertSame($post->title, $seo['image_alt']);
    }

    public function test_post_page_includes_enhanced_open_graph_tags(): void
    {
        $user = User::factory()->create(['name' => 'SEO Author']);
        $alias = $user->primaryAlias();
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'SEO Story',
            'slug' => 'seo-story',
            'subtitle' => 'A subtitle for social previews',
        ]);

        $this->get(route('posts.show', [$alias, $post->slug]))
            ->assertOk()
            ->assertSee('property="og:image:alt"', false)
            ->assertSee('property="article:section"', false)
            ->assertSee('property="article:modified_time"', false)
            ->assertSee('name="twitter:image:alt"', false)
            ->assertSee('A subtitle for social previews', false);
    }
}
