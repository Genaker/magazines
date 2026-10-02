<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_access_write_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('posts.create'))
            ->assertRedirect(route('verification.notice'));
    }
}
