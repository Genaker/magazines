<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Magazine;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateMagazineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_authenticated_user_can_create_magazine_via_api(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/magazines', [
            'name' => 'City Weekly',
            'description' => 'Local stories',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'City Weekly')
            ->assertJsonPath('data.slug', 'city-weekly')
            ->assertJsonStructure(['data' => ['id', 'url', 'owner_id']]);

        $this->assertDatabaseHas('magazines', [
            'owner_id' => $user->id,
            'name' => 'City Weekly',
        ]);

        $magazine = Magazine::query()->where('slug', 'city-weekly')->firstOrFail();
        $this->assertDatabaseHas('magazine_members', [
            'magazine_id' => $magazine->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_guest_cannot_create_magazine(): void
    {
        $this->postJson('/api/v1/magazines', [
            'name' => 'Blocked',
        ])->assertUnauthorized();
    }

    public function test_magazine_endpoint_hidden_when_feature_disabled(): void
    {
        Features::set('magazines', false);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/magazines', [
            'name' => 'Hidden',
        ])->assertNotFound();
    }
}
