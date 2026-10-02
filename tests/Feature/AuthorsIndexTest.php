<?php

namespace Tests\Feature;

use App\Models\AuthorAlias;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_authors_index_lists_active_aliases(): void
    {
        $owner = User::factory()->create();
        AuthorAlias::query()->create([
            'user_id' => $owner->id,
            'username' => 'demoauthor',
            'name' => 'Demo Author',
            'is_primary' => true,
        ]);

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertSee('Demo Author')
            ->assertSee(route('authors.show', 'demoauthor'), false);
    }

    public function test_authors_index_search_filters_results(): void
    {
        $owner = User::factory()->create();
        AuthorAlias::query()->create([
            'user_id' => $owner->id,
            'username' => 'demoauthor',
            'name' => 'Demo Author',
            'is_primary' => true,
        ]);
        AuthorAlias::query()->create([
            'user_id' => User::factory()->create()->id,
            'username' => 'other',
            'name' => 'Other Writer',
            'is_primary' => true,
        ]);

        $this->get(route('authors.index', ['q' => 'Demo']))
            ->assertOk()
            ->assertSee('Demo Author')
            ->assertDontSee('>Other Writer</h2>', false);
    }

    public function test_navigation_links_to_authors_index(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('authors.index').'"', false);
    }
}
