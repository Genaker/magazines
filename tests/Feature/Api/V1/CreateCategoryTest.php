<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_admin_can_create_site_category_via_api(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $response = $this->postJson('/api/v1/site-categories', [
            'name' => 'Technology',
            'description' => 'Tech news',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Technology')
            ->assertJsonPath('data.magazine_id', null)
            ->assertJsonStructure(['data' => ['id', 'slug', 'url']]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Technology',
            'magazine_id' => null,
        ]);
    }

    public function test_non_admin_cannot_create_site_category(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/site-categories', [
            'name' => 'Blocked',
        ])->assertForbidden();
    }

    public function test_magazine_owner_can_create_magazine_category_via_api(): void
    {
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/magazines/'.$magazine->id.'/categories', [
            'name' => 'Politics',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Politics')
            ->assertJsonPath('data.magazine_id', $magazine->id);

        $this->assertDatabaseHas('categories', [
            'name' => 'Politics',
            'magazine_id' => $magazine->id,
        ]);
    }

    public function test_magazine_writer_cannot_create_magazine_category(): void
    {
        $owner = User::factory()->create();
        $writer = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        Sanctum::actingAs($writer);

        $this->postJson('/api/v1/magazines/'.$magazine->id.'/categories', [
            'name' => 'Politics',
        ])->assertForbidden();
    }

    public function test_site_parent_cannot_be_used_for_magazine_category(): void
    {
        $owner = User::factory()->create();
        $siteCategory = Category::query()->create(['name' => 'Site Root', 'slug' => 'site-root']);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/magazines/'.$magazine->id.'/categories', [
            'name' => 'Politics',
            'parent_id' => $siteCategory->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }
}
