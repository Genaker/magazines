<?php

namespace Tests\Unit;

use App\Models\AuthorAlias;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorAliasMatchingSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_search_filters_by_name_username_and_bio(): void
    {
        $owner = User::factory()->create();
        AuthorAlias::query()->create([
            'user_id' => $owner->id,
            'username' => 'demoauthor',
            'name' => 'Demo Author',
            'bio' => 'Writes about culture.',
            'is_primary' => true,
        ]);
        AuthorAlias::query()->create([
            'user_id' => User::factory()->create()->id,
            'username' => 'techwriter',
            'name' => 'Tech Writer',
            'bio' => 'Artificial intelligence essays.',
            'is_primary' => true,
        ]);

        $this->assertSame(['Demo Author'], AuthorAlias::query()->active()->matchingSearch('Demo')->pluck('name')->all());
        $this->assertSame(['Tech Writer'], AuthorAlias::query()->active()->matchingSearch('techwriter')->pluck('name')->all());
        $this->assertSame(['Tech Writer'], AuthorAlias::query()->active()->matchingSearch('artificial')->pluck('name')->all());
        $this->assertSame(
            AuthorAlias::query()->active()->count(),
            AuthorAlias::query()->active()->matchingSearch('')->count(),
        );
    }

    public function test_active_scope_excludes_retired_aliases(): void
    {
        $owner = User::factory()->create();
        $alias = AuthorAlias::query()->create([
            'user_id' => $owner->id,
            'username' => 'retired',
            'name' => 'Retired Author',
            'is_primary' => true,
        ]);
        $alias->delete();

        $this->assertSame([], AuthorAlias::query()->active()->matchingSearch('Retired')->pluck('name')->all());
    }
}
