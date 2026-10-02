<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\CategoryRequestReviewed;
use App\Notifications\CategoryRequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CategoryRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_are_notified_when_category_is_requested(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('category-requests.store'), [
            'name' => 'Design',
            'reason' => 'We need a design category for UI posts.',
        ])->assertRedirect(route('category-requests.mine'));

        Notification::assertSentTo($admin, CategoryRequestSubmitted::class);
    }

    public function test_user_is_notified_when_request_is_approved(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('category-requests.store'), [
            'name' => 'Design',
            'reason' => 'We need a design category.',
        ]);

        $request = $user->categoryRequests()->first();

        $this->actingAs($admin)->post(route('admin.category-requests.approve', $request));

        Notification::assertSentTo($user, CategoryRequestReviewed::class);
    }
}
