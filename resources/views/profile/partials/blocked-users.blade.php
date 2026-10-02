<section class="border-t border-gray-200 pt-8">
    <h2 class="text-lg font-medium text-gray-900">Blocked users</h2>
    <p class="mt-1 text-sm text-gray-600">Blocked users cannot view your posts or comment on them.</p>

    @if (session('status') === 'user-unblocked')
        <p class="mt-3 text-sm text-green-700">User unblocked.</p>
    @endif

    @if ($user->blockedUsers->isEmpty())
        <p class="mt-4 text-sm text-gray-500">You have not blocked anyone. Block users from their profile page.</p>
    @else
        <ul class="mt-4 divide-y divide-gray-200 border border-gray-200 rounded-md">
            @foreach ($user->blockedUsers as $blocked)
                <li class="flex items-center justify-between px-4 py-3">
                    <div>
                        <a href="{{ route('authors.show', $blocked) }}" class="font-medium text-gray-900 hover:underline">{{ $blocked->name }}</a>
                        <span class="text-sm text-gray-500">{{ '@'.$blocked->username }}</span>
                    </div>
                    <form method="POST" action="{{ route('profile.blocked-users.destroy', $blocked) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline">Unblock</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</section>
