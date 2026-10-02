<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\DatabaseFullTextSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Verifies full-text indexes match committed rows (no open transaction). */
class DatabaseFullTextSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected array $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
    }

    public function test_post_fulltext_index_finds_committed_rows(): void
    {
        $this->assertFalse(DatabaseFullTextSearch::useLikeFallback());

        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Redis Search guide',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->assertSame(
            1,
            Post::query()
                ->where(function ($query): void {
                    DatabaseFullTextSearch::matchAny($query, ['title', 'subtitle', 'body'], 'Redis');
                })
                ->count(),
        );
    }

    public function test_author_alias_fulltext_index_finds_committed_rows(): void
    {
        $owner = User::factory()->create();
        AuthorAlias::query()->create([
            'user_id' => $owner->id,
            'username' => 'demoauthor',
            'name' => 'Demo Author',
            'bio' => 'Writes about culture.',
            'is_primary' => true,
        ]);

        $this->assertSame(
            ['Demo Author'],
            AuthorAlias::query()
                ->active()
                ->matchingSearch('Demo')
                ->pluck('name')
                ->all(),
        );
    }

    public function test_magazine_fulltext_index_finds_committed_rows(): void
    {
        $owner = User::factory()->create();
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
            'description' => 'A community magazine.',
        ]);

        $this->assertSame(
            1,
            Magazine::query()
                ->where(function ($query): void {
                    DatabaseFullTextSearch::matchAny($query, ['name', 'description', 'slug'], 'Commons');
                })
                ->count(),
        );
    }
}
