<?php

namespace Tests\Feature\Integration;

use App\Enums\MagazineJoinRequestStatus;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Magazine lifecycle: create → join request → approve → submit post → approve → public magazine page.
 */
class MagazineFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_magazine_submission_publish_flow(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $this->actingAs($owner)->post(route('magazines.store'), [
            'name' => 'Integration Weekly',
            'description' => 'Stories from our integration tests.',
        ])->assertRedirect();

        $magazine = Magazine::query()->where('name', 'Integration Weekly')->firstOrFail();

        $this->actingAs($writer)->post(route('magazines.join-requests.store', $magazine), [
            'message' => 'I write about software.',
        ])->assertRedirect();

        $joinRequest = MagazineJoinRequest::query()->where('user_id', $writer->id)->firstOrFail();

        $this->actingAs($owner)->post(route('magazines.join-requests.approve', [$magazine, $joinRequest]))
            ->assertRedirect();

        $this->assertSame(MagazineJoinRequestStatus::Approved, $joinRequest->fresh()->status);

        $this->actingAs($writer)->post(route('posts.store'), [
            'title' => 'Magazine Integration Draft',
            'body' => '<p>Pending review.</p>',
            'category_id' => $category->id,
            'magazine_id' => $magazine->id,
            'status' => 'draft',
        ])->assertRedirect();

        $post = Post::query()->where('user_id', $writer->id)->firstOrFail();

        $this->actingAs($writer)->post(route('magazines.posts.submit', [$magazine, $post]))
            ->assertRedirect();

        $this->actingAs($owner)->post(route('magazines.submissions.approve', [$magazine, $post]))
            ->assertRedirect();

        $post->refresh();
        $this->assertSame('approved', $post->magazine_submission_status->value);
        $this->assertSame('published', $post->status->value);

        $this->get(route('magazines.show', $magazine))
            ->assertOk()
            ->assertSee('Magazine Integration Draft');
    }
}
