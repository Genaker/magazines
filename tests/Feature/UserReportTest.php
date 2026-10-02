<?php

namespace Tests\Feature;

use App\Enums\UserReportStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_another_user(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();

        $this->actingAs($reporter)
            ->post(route('users.report', $reported), [
                'reason' => 'This user is posting spam repeatedly.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_reports', [
            'reporter_id' => $reporter->id,
            'reported_id' => $reported->id,
            'status' => UserReportStatus::Pending->value,
        ]);
    }

    public function test_admin_can_ban_user_from_report(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $reported = User::factory()->create();
        $report = UserReport::query()->create([
            'reporter_id' => $admin->id,
            'reported_id' => $reported->id,
            'reason' => 'Harassment in comments section.',
            'status' => UserReportStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.user-reports.ban', $report))
            ->assertRedirect();

        $this->assertTrue($reported->fresh()->is_banned);
        $this->assertSame(UserReportStatus::Reviewed, $report->fresh()->status);
    }
}
