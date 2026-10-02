<?php

namespace Tests\Feature\Integration;

use App\Enums\MagazineJoinRequestStatus;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\Post;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\MagazineSubdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Magazine subdomain flow: approve post → magazine home and post on {slug}.localhost,
 * path URLs redirect to magazine subdomain (not author subdomain).
 */
class MagazineSubdomainFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazine_subdomains', true);
        Features::set('author_subdomains', true);
        MagazineSubdomain::setRedirect(true);
        AuthorSubdomain::setRedirect(true);
        AuthorSubdomain::setBaseHost('localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_approved_magazine_post_lives_on_magazine_subdomain(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now(), 'username' => 'magowner']);
        $writer = User::factory()->create(['email_verified_at' => now(), 'username' => 'magwriter']);
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
            'title' => 'Magazine Subdomain Story',
            'body' => '<p>Published on the magazine subdomain.</p>',
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
        $post->load('authorAlias', 'magazine');
        $magazineHost = $magazine->slug.'.localhost';
        $magazinePostUrl = MagazineSubdomain::postUrl($post);

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertRedirect($magazinePostUrl);

        $this->get(route('magazines.show', $magazine))
            ->assertRedirect(MagazineSubdomain::magazineUrl($magazine));

        $this->withServerVariables(['HTTP_HOST' => $magazineHost])
            ->get('http://'.$magazineHost.'/')
            ->assertOk()
            ->assertSee('Integration Weekly')
            ->assertSee('Magazine Subdomain Story')
            ->assertSee($magazinePostUrl, false);

        $this->withServerVariables(['HTTP_HOST' => $magazineHost])
            ->get('http://'.$magazineHost.'/'.$post->slug)
            ->assertOk()
            ->assertSee('Magazine Subdomain Story');
    }
}
