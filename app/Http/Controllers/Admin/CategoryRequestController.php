<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CategoryRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryRequest;
use App\Models\Magazine;
use App\Notifications\CategoryRequestReviewed;
use App\Support\AdminGrid;
use App\Support\Features;
use App\Support\NotificationSender;
use App\Support\Slugger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryRequestController extends Controller
{
    public function index(Request $request): View
    {
        ['sort' => $sort, 'dir' => $dir, 'search' => $search] = AdminGrid::params(
            $request,
            ['name', 'status', 'created_at'],
            'created_at',
        );

        $requests = CategoryRequest::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.category-requests.index', [
            'requests' => $requests,
            'search' => $search,
            'sort' => $sort,
            'dir' => $dir,
            'parentOptions' => Category::validParentOptions(),
            'magazines' => Features::enabled('magazines')
                ? Magazine::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function approve(Request $request, CategoryRequest $categoryRequest): RedirectResponse
    {
        abort_if($categoryRequest->status !== CategoryRequestStatus::Pending, 422);

        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'magazine_id' => ['nullable', 'exists:magazines,id'],
        ]);

        $magazineId = filled($data['magazine_id'] ?? null) ? (int) $data['magazine_id'] : null;
        $parentId = filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;

        if ($parentId) {
            $parent = Category::query()->findOrFail($parentId);

            if (($parent->magazine_id ?? null) != ($magazineId ?: null)) {
                return back()->withErrors([
                    'parent_id' => 'Parent category must belong to the same magazine scope.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $categoryRequest, $parentId, $magazineId): void {
            Category::query()->firstOrCreate(
                ['slug' => Slugger::unique($categoryRequest->name, new Category, 'slug')],
                [
                    'name' => $categoryRequest->name,
                    'parent_id' => $parentId,
                    'magazine_id' => $magazineId,
                ],
            );

            $categoryRequest->update([
                'status' => CategoryRequestStatus::Approved,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        $categoryRequest->refresh()->load('user');
        NotificationSender::send($categoryRequest->user, new CategoryRequestReviewed($categoryRequest));

        return back()->with('status', 'Category request approved.');
    }

    public function reject(Request $request, CategoryRequest $categoryRequest): RedirectResponse
    {
        abort_if($categoryRequest->status !== CategoryRequestStatus::Pending, 422);

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $categoryRequest->update([
            'status' => CategoryRequestStatus::Rejected,
            'admin_note' => $data['admin_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $categoryRequest->refresh()->load('user');
        NotificationSender::send($categoryRequest->user, new CategoryRequestReviewed($categoryRequest));

        return back()->with('status', 'Category request rejected.');
    }
}
