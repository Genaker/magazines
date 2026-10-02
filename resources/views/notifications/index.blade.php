<x-app-layout>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">Notifications</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-900 underline">Mark all as read</button>
                </form>
            @endif
        </div>

        @if (session('status') === 'notifications-read')
            <p class="mb-4 text-sm text-green-700">All notifications marked as read.</p>
        @endif

        <div class="divide-y divide-gray-100">
            @forelse ($notifications as $notification)
                @include('partials.notification-item', ['notification' => $notification])
            @empty
                <p class="py-12 text-center text-gray-500">No notifications yet.</p>
            @endforelse
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
