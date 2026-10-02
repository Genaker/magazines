<?php

namespace App\Http\Controllers;

use App\Enums\UserReportStatus;
use App\Models\User;
use App\Models\UserReport;
use App\Services\UserReportNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserReportController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $report = UserReport::query()->updateOrCreate(
            [
                'reporter_id' => $request->user()->id,
                'reported_id' => $user->id,
            ],
            [
                'reason' => $data['reason'],
                'status' => UserReportStatus::Pending,
                'admin_note' => null,
            ],
        );

        UserReportNotifier::submitted($report);

        return back()->with('status', 'report-submitted');
    }
}
