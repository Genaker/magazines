<?php

namespace Tests\Feature;

use App\Enums\MagazineJoinRequestStatus;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\User;
use App\Support\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_render_on_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                Message::SESSION_KEY => [
                    Message::item(Message::TYPE_WARNING, 'You are already a member of this magazine.'),
                ],
            ])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('You are already a member of this magazine.')
            ->assertSee(__('message.dismiss'));
    }

    public function test_legacy_status_key_renders_in_message_banner(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['status' => 'magazine-join-requested'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('message.status.magazine-join-requested'));
    }

    public function test_not_found_responses_are_not_converted_to_message_banners(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post('/posts/999999/like')
            ->assertNotFound();
    }
}
