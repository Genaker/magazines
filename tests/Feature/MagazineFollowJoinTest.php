<?php

namespace Tests\Feature;

use App\Enums\MagazineJoinRequestStatus;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\Message;
use App\Support\MagazineSubdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineFollowJoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_unfollow_magazine(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Design Weekly',
            'slug' => 'design-weekly',
        ]);

        $this->actingAs($user)
            ->postJson(route('magazines.follow', $magazine))
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->assertTrue($user->followedMagazines()->where('magazine_id', $magazine->id)->exists());

        $this->actingAs($user)
            ->postJson(route('magazines.follow', $magazine))
            ->assertOk()
            ->assertJson(['following' => false]);
    }

    public function test_user_can_request_to_join_magazine(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);
        $magazine->members()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($applicant)
            ->post(route('magazines.join-requests.store', $magazine), [
                'message' => 'I write about AI.',
            ])
            ->assertRedirect(MagazineSubdomain::canonicalMagazineUrl($magazine));

        $this->assertDatabaseHas('magazine_join_requests', [
            'magazine_id' => $magazine->id,
            'user_id' => $applicant->id,
            'status' => MagazineJoinRequestStatus::Pending->value,
        ]);
    }

    public function test_editor_can_approve_join_request_and_add_writer(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);
        $magazine->members()->attach($owner->id, ['role' => 'owner']);

        $joinRequest = MagazineJoinRequest::query()->create([
            'magazine_id' => $magazine->id,
            'user_id' => $applicant->id,
            'status' => MagazineJoinRequestStatus::Pending,
        ]);

        $this->actingAs($owner)
            ->post(route('magazines.join-requests.approve', [$magazine, $joinRequest]))
            ->assertRedirect();

        $joinRequest->refresh();
        $this->assertSame(MagazineJoinRequestStatus::Approved, $joinRequest->status);
        $this->assertSame('writer', $magazine->memberRole($applicant));
    }

    public function test_owner_can_reject_join_request(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);
        $magazine->members()->attach($owner->id, ['role' => 'owner']);

        $joinRequest = MagazineJoinRequest::query()->create([
            'magazine_id' => $magazine->id,
            'user_id' => $applicant->id,
            'status' => MagazineJoinRequestStatus::Pending,
        ]);

        $this->actingAs($owner)
            ->post(route('magazines.join-requests.reject', [$magazine, $joinRequest]))
            ->assertRedirect();

        $this->assertSame(MagazineJoinRequestStatus::Rejected, $joinRequest->fresh()->status);
        $this->assertNull($magazine->memberRole($applicant));
    }

    public function test_existing_member_cannot_request_to_join(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $writer = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $this->actingAs($writer)
            ->post(route('magazines.join-requests.store', $magazine))
            ->assertRedirect(MagazineSubdomain::canonicalMagazineUrl($magazine))
            ->assertSessionHas(Message::SESSION_KEY);
    }

    public function test_member_on_magazine_subdomain_does_not_see_join_form(): void
    {
        Features::seedDefaults();
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);

        $writer = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        $this->actingAs($writer)
            ->withServerVariables(['HTTP_HOST' => 'terst.lvh.me'])
            ->get('http://terst.lvh.me:8888/')
            ->assertOk()
            ->assertSee(__('message.magazine_you_are_member'))
            ->assertDontSee('Request to join');
    }

    public function test_magazine_show_page_reflects_follow_and_join_state(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $visitor = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Visible Mag',
            'slug' => 'visible-mag',
        ]);

        $this->actingAs($visitor)
            ->get(route('magazines.show', $magazine))
            ->assertOk()
            ->assertSee('Follow')
            ->assertSee('Request to join');

        $visitor->followedMagazines()->attach($magazine->id);
        MagazineJoinRequest::query()->create([
            'magazine_id' => $magazine->id,
            'user_id' => $visitor->id,
            'status' => MagazineJoinRequestStatus::Pending,
        ]);

        $this->actingAs($visitor)
            ->get(route('magazines.show', $magazine))
            ->assertOk()
            ->assertSee('Join request pending');
    }

    public function test_member_cannot_submit_duplicate_join_request(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Future Tech',
            'slug' => 'future-tech',
        ]);

        MagazineJoinRequest::query()->create([
            'magazine_id' => $magazine->id,
            'user_id' => $applicant->id,
            'status' => MagazineJoinRequestStatus::Pending,
        ]);

        $this->actingAs($applicant)
            ->post(route('magazines.join-requests.store', $magazine))
            ->assertRedirect(MagazineSubdomain::canonicalMagazineUrl($magazine))
            ->assertSessionHas(Message::SESSION_KEY);
    }

    public function test_get_join_request_url_redirects_to_magazine_page(): void
    {
        Features::seedDefaults();
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);

        $magazine = Magazine::query()->create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);

        $this->get(route('magazines.join-requests.create', $magazine))
            ->assertRedirect(MagazineSubdomain::canonicalMagazineUrl($magazine));
    }

    public function test_join_request_from_magazine_subdomain_posts_to_main_site_route(): void
    {
        Features::seedDefaults();
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('localhost');
        config(['app.url' => 'http://localhost']);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Terst',
            'slug' => 'terst',
        ]);

        $this->actingAs($applicant)
            ->withServerVariables(['HTTP_HOST' => 'terst.localhost'])
            ->post('http://terst.localhost/magazine/terst/join-request', [
                'message' => 'I want in.',
            ])
            ->assertRedirect(MagazineSubdomain::canonicalMagazineUrl($magazine))
            ->assertSessionHas(Message::SESSION_KEY);
    }
}
