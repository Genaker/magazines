<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-2">Manage magazine</h1>
        <p class="text-gray-600 mb-6">{{ $magazine->name }}</p>

        @if (session('status') === 'submission-approved')
            <p class="mb-4 text-sm text-green-700">Submission approved and published.</p>
        @endif
        @if (session('status') === 'submission-rejected')
            <p class="mb-4 text-sm text-amber-700">Submission rejected.</p>
        @endif
        @if (session('status') === 'magazine-join-approved')
            <p class="mb-4 text-sm text-green-700">Join request approved.</p>
        @endif
        @if (session('status') === 'magazine-join-rejected')
            <p class="mb-4 text-sm text-amber-700">Join request rejected.</p>
        @endif
        @if (session('status') === 'member-invited')
            <p class="mb-4 text-sm text-green-700">Member invited.</p>
        @endif
        @if (session('status') === 'magazine-settings-updated')
            <p class="mb-4 text-sm text-green-700">{{ __('message.status.magazine-settings-updated') }}</p>
        @endif

        <section class="mb-10 rounded-lg border border-gray-200 p-4">
            <h2 class="text-xl font-semibold mb-3">{{ __('app.magazine_submission_settings') }}</h2>
            <form method="POST" action="{{ route('magazines.submission-settings.update', $magazine) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="require_post_approval" value="0">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="require_post_approval"
                           value="1"
                           class="mt-1 rounded border-gray-300"
                           @checked(old('require_post_approval', $magazine->require_post_approval))>
                    <span>
                        <span class="block text-sm font-medium">{{ __('app.magazine_require_post_approval') }}</span>
                        <span class="block text-xs text-gray-500 mt-1">{{ __('app.magazine_require_post_approval_help') }}</span>
                    </span>
                </label>
                <button type="submit" class="mt-4 px-4 py-2 bg-gray-900 text-white rounded-md text-sm">{{ __('app.save') }}</button>
            </form>
        </section>

        <section class="mb-10">
            <h2 class="text-xl font-semibold mb-4">Join requests</h2>
            @forelse ($joinRequests as $joinRequest)
                <div class="border-b border-gray-200 py-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold">
                            <a href="{{ route('authors.show', $joinRequest->user) }}" class="hover:underline">{{ $joinRequest->user->name }}</a>
                        </p>
                        <p class="text-sm text-gray-500">{{ '@'.$joinRequest->user->username }} · {{ $joinRequest->created_at->diffForHumans() }}</p>
                        @if ($joinRequest->message)
                            <p class="mt-2 text-sm text-gray-700 whitespace-pre-wrap">{{ $joinRequest->message }}</p>
                        @endif
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <form method="POST" action="{{ route('magazines.join-requests.approve', [$magazine, $joinRequest]) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-gray-900 text-white rounded text-sm">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('magazines.join-requests.reject', [$magazine, $joinRequest]) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1 border border-red-300 text-red-700 rounded text-sm">Reject</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-gray-600">No pending join requests.</p>
            @endforelse
        </section>

        <section class="mb-10">
            <h2 class="text-xl font-semibold mb-4">Story submissions</h2>
            @forelse ($submissions as $post)
                <div class="border-b border-gray-200 py-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ $post->title }}</p>
                        <p class="text-sm text-gray-500">by {{ $post->authorAlias?->name ?? $post->user->name }}@if ($post->category) · {{ $post->category->name }}@endif</p>
                        <a href="{{ route('posts.show', [$post->authorAlias, $post->slug]) }}" class="text-sm underline mt-1 inline-block">Preview</a>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <form method="POST" action="{{ route('magazines.submissions.approve', [$magazine, $post]) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-gray-900 text-white rounded text-sm">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('magazines.submissions.reject', [$magazine, $post]) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1 border border-red-300 text-red-700 rounded text-sm">Reject</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-gray-600">No pending submissions.</p>
            @endforelse
        </section>

        <section class="mb-10 border-t pt-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('app.magazine_members') }}</h2>
            @if ($members->isEmpty())
                <p class="text-gray-600">{{ __('app.no_magazine_members_yet') }}</p>
            @else
                <ul class="divide-y divide-gray-200 border border-gray-200 rounded-md">
                    @foreach ($members as $member)
                        @php
                            $role = $member->id === $magazine->owner_id
                                ? 'owner'
                                : ($member->pivot->role ?? 'writer');
                        @endphp
                        <li class="flex items-center justify-between gap-4 px-4 py-3">
                            <div>
                                <p class="font-medium">
                                    <a href="{{ route('authors.show', $member) }}" class="hover:underline">{{ $member->name }}</a>
                                </p>
                                <p class="text-sm text-gray-500">{{ '@'.$member->username }}</p>
                            </div>
                            <span class="text-sm capitalize text-gray-600">{{ $role }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="border-t pt-6">
            <h2 class="text-lg font-semibold mb-3">Invite member</h2>
            <form method="POST" action="{{ route('magazines.members.invite', $magazine) }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Username</label>
                    <input type="text" name="username" class="rounded-md border-gray-300" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Role</label>
                    <select name="role" class="rounded-md border-gray-300">
                        <option value="writer">Writer</option>
                        <option value="editor">Editor</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">Invite</button>
            </form>
        </section>
    </div>
</x-app-layout>
