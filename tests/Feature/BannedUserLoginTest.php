<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannedUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'is_banned' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
