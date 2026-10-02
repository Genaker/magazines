<?php

namespace Tests\Feature\Integration;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API v1 → web: magazines, site categories, magazine categories, and posts.
 */
class ApiContentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_api_created_magazine_is_visible_on_web(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $token = $owner->createToken('integration')->plainTextToken;

        $this->postJson('/api/v1/magazines', [
            'name' => 'API Weekly',
            'description' => 'Created through the HTTP API.',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'api-weekly');

        $magazine = Magazine::query()->where('slug', 'api-weekly')->firstOrFail();

        $this->get(route('magazines.show', $magazine))
            ->assertOk()
            ->assertSee('API Weekly')
            ->assertSee('Created through the HTTP API.');

        Sanctum::actingAs($owner);

        $this->get(route('magazines.mine'))
            ->assertOk()
            ->assertSee('API Weekly');
    }

    public function test_api_created_site_category_enables_posting_and_public_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $author = User::factory()->create(['username' => 'apicatwriter']);
        $adminToken = $admin->createToken('admin-integration')->plainTextToken;
        $authorToken = $author->createToken('author-integration')->plainTextToken;

        $this->postJson('/api/v1/site-categories', [
            'name' => 'API Design',
            'description' => 'Design stories from the API.',
        ], [
            'Authorization' => 'Bearer '.$adminToken,
        ])
            ->assertCreated()
            ->assertJsonPath('data.magazine_id', null);

        $category = Category::query()->where('slug', 'api-design')->firstOrFail();

        $this->postJson('/api/v1/posts', [
            'title' => 'API Category Story',
            'body' => '<p>Posted into an API-created site category.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ], [
            'Authorization' => 'Bearer '.$authorToken,
        ])
            ->assertCreated()
            ->assertJsonPath('data.category_id', $category->id);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee('API Category Story');

        $this->assertTrue(
            Category::cachedForNavigation()->contains(fn (Category $item) => $item->slug === 'api-design'),
        );
    }

    public function test_api_magazine_category_and_post_appear_on_magazine_page(): void
    {
        $owner = User::factory()->create(['username' => 'apiowner', 'email_verified_at' => now()]);
        $token = $owner->createToken('magazine-integration')->plainTextToken;

        $magazineResponse = $this->postJson('/api/v1/magazines', [
            'name' => 'API City Weekly',
            'description' => 'Local API stories.',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

        $magazineId = (int) $magazineResponse->json('data.id');

        $categoryResponse = $this->postJson('/api/v1/magazines/'.$magazineId.'/categories', [
            'name' => 'API Politics',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

        $categoryId = (int) $categoryResponse->json('data.id');
        $categorySlug = (string) $categoryResponse->json('data.slug');

        $this->postJson('/api/v1/posts', [
            'title' => 'API Magazine Beat Story',
            'body' => '<p>Published in an API-created magazine category.</p>',
            'category_id' => $categoryId,
            'magazine_id' => $magazineId,
            'status' => 'published',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertCreated()
            ->assertJsonPath('data.magazine_id', $magazineId);

        $post = Post::query()->where('title', 'API Magazine Beat Story')->firstOrFail();
        $magazine = Magazine::query()->findOrFail($magazineId);

        $this->get(route('magazines.show', $magazine))
            ->assertOk()
            ->assertSee('API Politics')
            ->assertSee('API Magazine Beat Story');

        $this->get(route('magazines.show', [$magazine, 'category' => $categorySlug]))
            ->assertOk()
            ->assertSee('API Magazine Beat Story');

        $this->get(route('posts.show', [$owner->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('API Magazine Beat Story');

        $this->get(route('authors.show', $owner->primaryAlias()))
            ->assertOk()
            ->assertSee('API Magazine Beat Story');
    }
}
