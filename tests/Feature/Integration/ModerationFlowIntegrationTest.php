<?php

namespace Tests\Feature\Integration;

use App\Enums\UserReportStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use App\Models\UserReport;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moderation chain: report user → admin ban → banned user blocked from writing.
 */
class ModerationFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_report_to_ban_blocks_authoring(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $reporter = User::factory()->create();
        $offender = User::factory()->create();
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($reporter)->post(route('users.report', $offender), [
            'reason' => 'Repeated harassment in comments.',
        ]);

        $report = UserReport::query()->where('reported_id', $offender->id)->firstOrFail();
        $this->assertSame(UserReportStatus::Pending, $report->status);

        $this->actingAs($admin)->post(route('admin.user-reports.ban', $report));

        $this->assertTrue($offender->fresh()->is_banned);
        $this->assertSame(UserReportStatus::Reviewed, $report->fresh()->status);

        $this->actingAs($offender->fresh())->get(route('home'))
            ->assertRedirect(route('login'));

        $this->actingAs($offender->fresh())->post(route('posts.store'), [
            'title' => 'Should not publish',
            'body' => '<p>Nope</p>',
            'category_id' => $category->id,
            'status' => 'draft',
        ])->assertRedirect(route('login'));
    }
}
