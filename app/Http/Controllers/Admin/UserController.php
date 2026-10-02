<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminGrid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        ['sort' => $sort, 'dir' => $dir, 'search' => $search] = AdminGrid::params(
            $request,
            ['name', 'email', 'username', 'role', 'created_at'],
            'created_at',
        );

        $users = AdminGrid::applySearch(User::query(), $search, ['name', 'email', 'username'])
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'sort', 'dir'));
    }

    public function edit(User $user): View
    {
        $user->load('authorAliases');

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,admin,super_admin'],
            'is_banned' => ['required', 'boolean'],
        ]);

        if ($data['role'] === UserRole::SuperAdmin->value && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        $user->update([
            'role' => $data['role'],
            'is_banned' => $request->boolean('is_banned'),
        ]);

        return redirect()->route('admin.users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            abort(403);
        }

        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('admin.users.index');
    }
}
