<?php

namespace Tests\Feature;

use App\Models\Magazine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_magazines_by_name(): void
    {
        $owner = User::factory()->create();
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
            'description' => 'A community magazine.',
        ]);

        $this->get(route('search', ['q' => 'Commons']))
            ->assertOk()
            ->assertSee('The Commons')
            ->assertSee(route('magazines.show', 'the-commons'), false);
    }

    public function test_search_finds_magazines_by_description(): void
    {
        $owner = User::factory()->create();
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
            'description' => 'Stories about artificial intelligence.',
        ]);

        $this->get(route('search', ['q' => 'artificial']))
            ->assertOk()
            ->assertSee('Future Tech');
    }

    public function test_magazines_index_filters_by_search_query(): void
    {
        $owner = User::factory()->create();
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Design Weekly',
            'slug' => 'design-weekly',
        ]);

        $this->get(route('magazines.index', ['q' => 'Commons']))
            ->assertOk()
            ->assertSee('The Commons')
            ->assertDontSee('>Design Weekly</h2>', false);
    }
}
