<?php

namespace App\Http\Controllers;

use App\Models\AuthorAlias;
use App\Support\ActiveAuthorAlias;
use App\Support\SubdomainLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthorAliasController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeAlias = ActiveAuthorAlias::resolve($user);

        return view('profile.aliases', [
            'aliases' => $user->authorAliases()->orderByDesc('is_primary')->orderBy('name')->get(),
            'activeAlias' => $activeAlias,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge([
            'username' => SubdomainLabel::forNickname((string) $request->input('username', '')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('author_aliases', 'username'),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $alias = $user->authorAliases()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'bio' => $data['bio'] ?? null,
            'is_primary' => false,
        ]);

        ActiveAuthorAlias::set($user, $alias);

        return redirect()
            ->route('profile.aliases')
            ->with('status', 'alias-created');
    }

    public function switch(Request $request, AuthorAlias $alias): RedirectResponse
    {
        $this->authorize('use', $alias);

        ActiveAuthorAlias::set($request->user(), $alias);

        return redirect()->back()->with('status', 'alias-switched');
    }

    public function destroy(Request $request, AuthorAlias $alias): RedirectResponse
    {
        $this->authorize('delete', $alias);

        $user = $request->user();

        if ($user->authorAliases()->count() <= 1) {
            return redirect()
                ->route('profile.aliases')
                ->withErrors(['alias' => 'You must keep at least one author alias.']);
        }

        if ($alias->is_primary) {
            $replacement = $user->authorAliases()
                ->whereKeyNot($alias->id)
                ->orderBy('created_at')
                ->first();

            $replacement?->update(['is_primary' => true]);
        }

        if (ActiveAuthorAlias::resolve($user)->is($alias)) {
            $next = $user->authorAliases()->whereKeyNot($alias->id)->orderByDesc('is_primary')->first();
            ActiveAuthorAlias::set($user, $next);
        }

        $alias->delete();

        return redirect()
            ->route('profile.aliases')
            ->with('status', 'alias-deleted');
    }
}
