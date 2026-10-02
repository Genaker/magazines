<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\CategoryRequest;
use App\Models\Post;
use App\Models\User;
use App\Notifications\AuthorPostPublished;
use App\Notifications\CategoryRequestReceived;
use App\Notifications\CategoryRequestSubmitted;
use App\Notifications\UserReportReceived;
use App\Notifications\UserReportSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActionEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_request_emails_admin_and_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => UserRole::Admin,
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post(route('category-requests.store'), [
            'name' => 'Local News',
            'reason' => 'We need a dedicated local news category.',
        ]);

        Notification::assertSentTo($admin, CategoryRequestSubmitted::class);
        Notification::assertSentTo($user, CategoryRequestReceived::class);
    }

    public function test_user_report_emails_admin_and_reporter(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => UserRole::Admin,
        ]);
        $reporter = User::factory()->create(['email_verified_at' => now()]);
        $reported = User::factory()->create(['email_verified_at' => now(), 'username' => 'reporteduser']);

        $this->actingAs($reporter)->post(route('users.report', $reported), [
            'reason' => 'This account is posting spam repeatedly.',
        ]);

        Notification::assertSentTo($admin, UserReportSubmitted::class);
        Notification::assertSentTo($reporter, UserReportReceived::class);
    }

    public function test_publishing_post_emails_author_confirmation(): void
    {
        Notification::fake();

        $author = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'My published story',
            'slug' => 'my-published-story',
            'body' => '<p>Hello world</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        Notification::assertSentTo($author, AuthorPostPublished::class);
    }
}
