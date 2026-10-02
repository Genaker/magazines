<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryParentOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_parent_options_returns_site_categories_when_no_magazine_selected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $siteCategory = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $magazine = Magazine::query()->create([
            'owner_id' => $admin->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Essays',
            'slug' => 'essays',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.categories.parent-options'))
            ->assertOk()
            ->assertJsonPath('options.0.id', $siteCategory->id)
            ->assertJsonMissing(['options' => [['name' => 'Essays']]]);
    }

    public function test_parent_options_returns_magazine_categories_when_magazine_selected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $magazine = Magazine::query()->create([
            'owner_id' => $admin->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        $essays = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Essays',
            'slug' => 'essays',
        ]);
        $news = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'News',
            'slug' => 'news',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.categories.parent-options', ['magazine_id' => $magazine->id]))
            ->assertOk();

        $ids = collect($response->json('options'))->pluck('id')->all();

        $this->assertContains($essays->id, $ids);
        $this->assertContains($news->id, $ids);
        $this->assertNotContains(Category::query()->where('slug', 'culture')->value('id'), $ids);
    }

    public function test_parent_options_excludes_current_category_and_descendants_on_edit(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $magazine = Magazine::query()->create([
            'owner_id' => $admin->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        $parent = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'News',
            'slug' => 'news',
        ]);
        $child = Category::query()->create([
            'magazine_id' => $magazine->id,
            'parent_id' => $parent->id,
            'name' => 'Local',
            'slug' => 'local',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.categories.parent-options', [
                'magazine_id' => $magazine->id,
                'exclude' => $parent->id,
            ]))
            ->assertOk();

        $ids = collect($response->json('options'))->pluck('id')->all();

        $this->assertNotContains($parent->id, $ids);
        $this->assertNotContains($child->id, $ids);
    }
}
