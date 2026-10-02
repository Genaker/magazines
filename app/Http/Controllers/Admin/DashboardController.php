<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryRequest;
use App\Models\Post;
use App\Models\UserReport;
use App\Models\User;
use App\Enums\CategoryRequestStatus;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'posts' => Post::query()->count(),
                'reports' => UserReport::query()->count(),
                'pending_category_requests' => CategoryRequest::query()->where('status', CategoryRequestStatus::Pending)->count(),
            ],
            'topPosts' => Post::query()->orderByDesc('views_count')->limit(5)->get(),
        ]);
    }
}
