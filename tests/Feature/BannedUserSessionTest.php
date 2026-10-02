<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannedUserSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_banned_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create(['is_banned' => true]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('login'));
    }
}
