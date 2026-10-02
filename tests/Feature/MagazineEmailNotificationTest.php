<?php

namespace Tests\Feature;

use App\Enums\MagazineJoinRequestStatus;
use App\Enums\MagazineSubmissionStatus;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MagazineJoinRequestApproved;
use App\Notifications\MagazineJoinRequestReceived;
use App\Notifications\MagazineJoinRequestSubmitted;
use App\Notifications\MagazinePostApproved;
use App\Notifications\MagazinePostSubmissionReceived;
use App\Notifications\MagazinePostSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MagazineEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_request_sends_email_to_magazine_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);

        $this->actingAs($applicant)->post(route('magazines.join-requests.store', $magazine), [
            'message' => 'I write about AI.',
        ]);

        Notification::assertSentTo($owner, MagazineJoinRequestSubmitted::class);
        Notification::assertSentTo($applicant, MagazineJoinRequestReceived::class);
    }

    public function test_join_request_approval_sends_email_to_applicant(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);

        $joinRequest = MagazineJoinRequest::query()->create([
            'magazine_id' => $magazine->id,
            'user_id' => $applicant->id,
            'status' => MagazineJoinRequestStatus::Pending,
        ]);

        $this->actingAs($owner)->post(route('magazines.join-requests.approve', [$magazine, $joinRequest]));

        Notification::assertSentTo($applicant, MagazineJoinRequestApproved::class);
    }

    public function test_post_submission_sends_email_to_magazine_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $post = Post::factory()->for($writer)->create([
            'category_id' => $category->id,
            'status' => 'published',
            'title' => 'Submitted story',
        ]);

        $this->actingAs($writer)->post(route('magazines.posts.submit', [$magazine, $post]));

        Notification::assertSentTo($owner, MagazinePostSubmitted::class);
        Notification::assertSentTo($writer, MagazinePostSubmissionReceived::class);
    }

    public function test_post_approval_sends_email_to_author(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);

        $post = Post::factory()->for($writer)->create([
            'category_id' => $category->id,
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Pending,
            'status' => 'draft',
            'title' => 'Pending story',
        ]);

        $this->actingAs($owner)->post(route('magazines.submissions.approve', [$magazine, $post]));

        Notification::assertSentTo($writer, MagazinePostApproved::class);
    }

    public function test_resaving_pending_post_does_not_resend_submission_email(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $post = Post::factory()->for($writer)->create([
            'category_id' => $category->id,
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Pending,
            'status' => 'published',
            'title' => 'Pending story',
        ]);

        Notification::fake();

        $this->actingAs($writer)->put(route('posts.update', $post), [
            'title' => 'Pending story',
            'body' => 'Updated body text for the pending story.',
            'status' => 'published',
            'magazine_id' => $magazine->id,
            'category_id' => $category->id,
        ]);

        Notification::assertNothingSent();
    }
}
