<?php

namespace Tests\Feature;

use App\Models\Magazine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineMineTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_owned_and_member_magazines(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);

        $owned = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Owned Magazine',
            'slug' => 'owned-magazine',
        ]);
        $owned->members()->attach($owner->id, ['role' => 'owner']);

        $joined = Magazine::query()->create([
            'owner_id' => $writer->id,
            'name' => 'Joined Magazine',
            'slug' => 'joined-magazine',
        ]);
        $joined->members()->attach($owner->id, ['role' => 'writer']);

        $response = $this->actingAs($owner)->get(route('magazines.mine'));

        $response->assertOk();
        $response->assertSee('Owned Magazine', false);
        $response->assertSee('Joined Magazine', false);
        $response->assertSee('0 published stories', false);
        $response->assertSee('Create magazine', false);
    }

    public function test_empty_state_shows_create_magazine_button(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('magazines.mine'));

        $response->assertOk();
        $response->assertSee('You have not created or joined any magazines yet.', false);
        $response->assertSee('Create magazine', false);
    }
}
