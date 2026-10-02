<?php

namespace Tests\Feature;

use App\Enums\MagazineSubmissionStatus;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineAuthorProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_approved_magazine_post_appears_on_author_profile(): void
    {
        $owner = User::factory()->create(['username' => 'magprofile']);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        $category = Category::query()->create([
            'name' => 'Politics',
            'slug' => 'politics',
            'magazine_id' => $magazine->id,
        ]);

        Post::factory()->for($owner)->create([
            'title' => 'Magazine Story On Profile',
            'slug' => 'magazine-story-on-profile',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'category_id' => $category->id,
        ]);

        $this->get(route('authors.show', $owner->primaryAlias()))
            ->assertOk()
            ->assertSee('Magazine Story On Profile');
    }

    public function test_pending_magazine_post_is_hidden_from_public_author_profile(): void
    {
        $magOwner = User::factory()->create();
        $writer = User::factory()->create(['username' => 'pendingwriter']);
        $magazine = Magazine::query()->create([
            'owner_id' => $magOwner->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);
        $category = Category::query()->create([
            'name' => 'Politics',
            'slug' => 'politics',
            'magazine_id' => $magazine->id,
        ]);

        Post::factory()->for($writer)->create([
            'title' => 'Pending Magazine Story',
            'slug' => 'pending-magazine-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Pending,
            'category_id' => $category->id,
        ]);

        $this->get(route('authors.show', $writer->primaryAlias()))
            ->assertOk()
            ->assertDontSee('Pending Magazine Story');
    }

    public function test_pending_magazine_post_is_hidden_from_home_feed(): void
    {
        $magOwner = User::factory()->create();
        $writer = User::factory()->create(['username' => 'feedwriter']);
        $magazine = Magazine::query()->create([
            'owner_id' => $magOwner->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        $magazine->members()->attach($writer->id, ['role' => 'writer']);

        Post::factory()->for($writer)->create([
            'title' => 'Pending Feed Story',
            'slug' => 'pending-feed-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Pending,
        ]);

        Post::factory()->for($writer)->create([
            'title' => 'Approved Feed Story',
            'slug' => 'approved-feed-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Pending Feed Story')
            ->assertSee('Approved Feed Story');
    }
}
