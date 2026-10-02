<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserReportStatus;
use App\Http\Controllers\Controller;
use App\Models\UserReport;
use App\Support\AdminGrid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserReportController extends Controller
{
    public function index(Request $request): View
    {
        ['sort' => $sort, 'dir' => $dir, 'search' => $search] = AdminGrid::params(
            $request,
            ['status', 'created_at'],
            'created_at',
        );

        $reports = UserReport::query()
            ->with(['reporter', 'reported'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('reported', fn ($user) => $user->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.user-reports.index', compact('reports', 'search', 'sort', 'dir'));
    }

    public function dismiss(UserReport $userReport): RedirectResponse
    {
        $userReport->update([
            'status' => UserReportStatus::Dismissed,
        ]);

        return back();
    }

    public function ban(UserReport $userReport, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $userReport->reported->update(['is_banned' => true]);
        $userReport->update([
            'status' => UserReportStatus::Reviewed,
            'admin_note' => $data['admin_note'] ?? 'Banned by admin after report.',
        ]);

        return back();
    }
}
