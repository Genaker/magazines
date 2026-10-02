<?php

namespace Tests\Unit;

use App\Models\Magazine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineMemberRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_role_returns_owner_for_magazine_owner(): void
    {
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Weekly',
            'slug' => 'weekly',
        ]);

        $this->assertSame('owner', $magazine->memberRole($owner));
    }

    public function test_member_role_returns_pivot_role_for_members(): void
    {
        $owner = User::factory()->create();
        $writer = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Weekly',
            'slug' => 'weekly',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $this->assertSame('writer', $magazine->memberRole($writer));
    }

    public function test_member_role_returns_null_for_non_members(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Weekly',
            'slug' => 'weekly',
        ]);

        $this->assertNull($magazine->memberRole($outsider));
    }
}
