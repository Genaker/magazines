<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\ModeratorAssigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::query()
            ->withCount(['posts as published_posts_count' => fn ($query) => $query->published()])
            ->orderByDesc('published_posts_count')
            ->orderBy('name')
            ->paginate(50);

        return view('admin.tags.index', compact('tags'));
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', [
            'tag' => $tag,
            'moderatorEmails' => implode("\n", ModeratorAssigner::emailsFor($tag)),
        ]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'moderator_emails' => ['nullable', 'string'],
        ]);

        $emails = preg_split('/[\s,]+/', (string) ($data['moderator_emails'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        ModeratorAssigner::syncFromEmails($tag, $emails);

        return redirect()->route('admin.tags.index')->with('status', 'Tag moderators updated.');
    }
}
