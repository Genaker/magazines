<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUsernameTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_username(): void
    {
        $user = User::factory()->create(['username' => 'oldname']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'username' => 'newname',
                'email' => $user->email,
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('newname', $user->fresh()->username);
    }

    public function test_profile_update_accepts_at_prefixed_username(): void
    {
        $user = User::factory()->create(['username' => 'oldname']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'username' => '@newname',
                'email' => $user->email,
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('newname', $user->fresh()->username);
    }
}
