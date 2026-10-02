<?php

namespace Tests\Feature;

use App\Enums\MagazineSubmissionStatus;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use App\Support\AuthorSubdomain;
use App\Support\MagazinePostSubmission;
use App\Support\MagazineSubdomain;
use App\Support\PostUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazinePostSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_publish_with_magazine_auto_approves_and_redirects_to_magazine_subdomain(): void
    {
        $this->enableMagazineSubdomains();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);

        $post = Post::factory()->for($owner)->create([
            'category_id' => $category->id,
            'status' => 'draft',
            'title' => 'Magazine story',
        ]);

        $response = $this->actingAs($owner)->put(route('posts.update', $post), [
            'title' => 'Magazine story',
            'body' => 'Body text for the magazine story.',
            'status' => 'published',
            'magazine_id' => $magazine->id,
            'category_id' => $category->id,
        ]);

        $post->refresh();

        $this->assertSame(MagazineSubmissionStatus::Approved, $post->magazine_submission_status);
        $this->assertSame($magazine->id, $post->magazine_id);

        $response->assertRedirect(PostUrl::canonical($post));
        $response->assertSessionHas('messages');
    }

    public function test_writer_publish_with_magazine_sets_pending_status(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $post = Post::factory()->for($writer)->create([
            'category_id' => $category->id,
            'status' => 'draft',
            'title' => 'Writer story',
        ]);

        $this->actingAs($writer)->put(route('posts.update', $post), [
            'title' => 'Writer story',
            'body' => 'Body text for the writer story.',
            'status' => 'published',
            'magazine_id' => $magazine->id,
            'category_id' => $category->id,
        ]);

        $post->refresh();

        $this->assertSame(MagazineSubmissionStatus::Pending, $post->magazine_submission_status);
    }

    public function test_clearing_magazine_clears_submission_status(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);

        $post = Post::factory()->for($owner)->create([
            'category_id' => $category->id,
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'status' => 'published',
            'title' => 'Was in magazine',
        ]);

        $this->actingAs($owner)->put(route('posts.update', $post), [
            'title' => 'Was in magazine',
            'body' => 'Body text.',
            'status' => 'published',
            'magazine_id' => '',
            'category_id' => $category->id,
        ]);

        $post->refresh();

        $this->assertNull($post->magazine_id);
        $this->assertNull($post->magazine_submission_status);
    }

    public function test_non_member_cannot_post_to_magazine(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $author = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);

        $post = Post::factory()->for($author)->create([
            'category_id' => $category->id,
            'status' => 'draft',
            'title' => 'Outside author',
        ]);

        $this->actingAs($author)->put(route('posts.update', $post), [
            'title' => 'Outside author',
            'body' => 'Body text from a new contributor.',
            'status' => 'published',
            'magazine_id' => $magazine->id,
            'category_id' => $category->id,
        ])->assertSessionHasErrors('magazine_id');

        $post->refresh();
        $magazine->refresh();

        $this->assertNull($post->magazine_id);
        $this->assertNull($magazine->memberRole($author));
    }

    public function test_non_member_cannot_submit_existing_post_to_magazine(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $author = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);

        $post = Post::factory()->for($author)->create([
            'category_id' => $category->id,
            'status' => 'published',
            'title' => 'Submit flow',
        ]);

        $this->actingAs($author)
            ->post(route('magazines.posts.submit', [$magazine, $post]))
            ->assertForbidden();
    }

    public function test_write_form_lists_only_joined_magazines(): void
    {
        Features::set('magazines', true);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);

        $joined = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Joined Mag',
            'slug' => 'joined-mag',
        ]);
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Other Mag',
            'slug' => 'other-mag',
        ]);
        $joined->members()->attach($writer->id, ['role' => 'writer']);

        $this->actingAs($writer)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('>Joined Mag</option>', false)
            ->assertDontSee('>Other Mag</option>', false);
    }

    public function test_writer_publish_auto_approves_when_magazine_does_not_require_approval(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Open Mag',
            'slug' => 'open-mag',
            'require_post_approval' => false,
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $post = Post::factory()->for($writer)->create([
            'category_id' => $category->id,
            'status' => 'draft',
            'title' => 'Open magazine story',
        ]);

        $this->actingAs($writer)->put(route('posts.update', $post), [
            'title' => 'Open magazine story',
            'body' => 'Body text.',
            'status' => 'published',
            'magazine_id' => $magazine->id,
            'category_id' => $category->id,
        ]);

        $post->refresh();

        $this->assertSame(MagazineSubmissionStatus::Approved, $post->magazine_submission_status);
    }

    public function test_magazine_post_submission_status_on_save(): void
    {
        $owner = User::factory()->create();
        $writer = User::factory()->create();

        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $this->assertSame(
            MagazineSubmissionStatus::Approved,
            MagazinePostSubmission::statusOnSave($owner, $magazine, \App\Enums\PostStatus::Published),
        );

        $this->assertNull(
            MagazinePostSubmission::statusOnSave($owner, $magazine, \App\Enums\PostStatus::Draft),
        );

        $this->assertSame(
            MagazineSubmissionStatus::Pending,
            MagazinePostSubmission::statusOnSave($writer, $magazine, \App\Enums\PostStatus::Published),
        );

        $magazine->update(['require_post_approval' => false]);

        $this->assertSame(
            MagazineSubmissionStatus::Approved,
            MagazinePostSubmission::statusOnSave($writer, $magazine, \App\Enums\PostStatus::Published),
        );
    }

    private function enableMagazineSubdomains(): void
    {
        Features::seedDefaults();
        Features::set('magazines', true);
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);
        \App\Support\SubdomainSession::configure();
    }
}
